<?php

namespace App\Services\Wallet;

use RuntimeException;
use ZipArchive;

/**
 * Turns a set of files into a signed `.pkpass` bundle.
 *
 * A pass is a zip containing:
 *   • pass.json        — what the pass says and how it looks
 *   • the images       — icon.png is mandatory, logo/strip/etc. optional
 *   • manifest.json    — {"filename": "<sha1 of its bytes>"} for every file above
 *   • signature        — a detached PKCS#7 signature of manifest.json, in DER,
 *                        made with the Pass Type ID certificate and chained to
 *                        Apple's WWDR intermediate
 *
 * iOS validates the chain, the Pass Type ID and the Team ID before it will even
 * show the "Add" sheet, and it reports every failure the same way: "Safari
 * cannot download this file". So this class fails loudly on the server instead
 * — a RuntimeException with the actual reason beats a silent bad pass.
 *
 * Deliberately dependency-free: signing is `openssl_pkcs7_sign` and zipping is
 * ext-zip, both already required by the rest of the platform. A composer
 * package for this would be ~200 lines of the same calls plus an upgrade path
 * to worry about on a shared host.
 */
class PkPassSigner
{
    public function __construct(
        private readonly string $certificatePath,
        private readonly string $certificatePassword,
        private readonly string $wwdrPath,
    ) {
    }

    /**
     * @param  array<string,string>  $files  filename => raw bytes (must include
     *                                       pass.json and icon.png)
     * @return string  the raw `.pkpass` bytes
     */
    public function build(array $files): string
    {
        if (! isset($files['pass.json'])) {
            throw new RuntimeException('A pass bundle must contain pass.json.');
        }
        if (! isset($files['icon.png'])) {
            throw new RuntimeException('A pass bundle must contain icon.png.');
        }

        $files['manifest.json'] = $this->manifest($files);
        $files['signature'] = $this->sign($files['manifest.json']);

        return $this->zip($files);
    }

    /**
     * SHA-1 of each file, keyed by name. SHA-1 is not a choice — it is what the
     * pass format specifies, and iOS compares against exactly this.
     *
     * @param  array<string,string>  $files
     */
    private function manifest(array $files): string
    {
        $digests = [];
        foreach ($files as $name => $contents) {
            $digests[$name] = sha1($contents);
        }

        // JSON_UNESCAPED_SLASHES so a filename never gains a backslash the
        // digest lookup would then miss.
        return json_encode($digests, JSON_UNESCAPED_SLASHES);
    }

    /**
     * Detached PKCS#7 signature over the manifest, converted to DER.
     */
    private function sign(string $manifest): string
    {
        if (! is_readable($this->certificatePath)) {
            throw new RuntimeException(
                "Pass Type ID certificate not readable at {$this->certificatePath}."
            );
        }
        if (! is_readable($this->wwdrPath)) {
            throw new RuntimeException(
                "Apple WWDR certificate not readable at {$this->wwdrPath}."
            );
        }

        $pkcs12 = file_get_contents($this->certificatePath);
        $parsed = [];
        if (! openssl_pkcs12_read($pkcs12, $parsed, $this->certificatePassword)) {
            throw new RuntimeException(
                'Could not open the Pass Type ID certificate — wrong password, '
                . 'or the file is not a PKCS#12 bundle. ('
                . (openssl_error_string() ?: 'no OpenSSL detail') . ')'
            );
        }

        // openssl_pkcs7_sign only works on files, so the manifest and the
        // signature both go through a scratch pair that is removed either way.
        $manifestFile = tempnam(sys_get_temp_dir(), 'pkpass_manifest_');
        $signatureFile = tempnam(sys_get_temp_dir(), 'pkpass_signature_');

        try {
            file_put_contents($manifestFile, $manifest);

            $signed = openssl_pkcs7_sign(
                $manifestFile,
                $signatureFile,
                $parsed['cert'],
                [$parsed['pkey'], $this->certificatePassword],
                [],
                PKCS7_BINARY | PKCS7_DETACHED,
                // The WWDR intermediate travels inside the signature; without
                // it the phone cannot build a chain to Apple's root and
                // rejects the pass.
                $this->wwdrPath
            );

            if (! $signed) {
                throw new RuntimeException(
                    'Signing the pass manifest failed. ('
                    . (openssl_error_string() ?: 'no OpenSSL detail') . ')'
                );
            }

            return $this->derFromSmime((string) file_get_contents($signatureFile));
        } finally {
            @unlink($manifestFile);
            @unlink($signatureFile);
        }
    }

    /**
     * Pulls the raw DER signature out of what openssl_pkcs7_sign actually
     * writes.
     *
     * Despite PKCS7_DETACHED, OpenSSL does not hand back a bare signature: it
     * writes a full `multipart/signed` S/MIME message whose first part is the
     * signed content and whose second is the base64 `application/x-pkcs7-
     * signature`. The pass format wants only that second part, decoded. Taking
     * "everything after the first blank line" — the recipe that circulates for
     * this — yields the *content* part instead, and the resulting pass fails on
     * the phone with no diagnostic beyond "Safari cannot download this file".
     *
     * Some OpenSSL builds emit a single-part signature message instead, so both
     * shapes are handled.
     */
    private function derFromSmime(string $smime): string
    {
        // Tolerate both line endings: the header/body separator is the first
        // empty line, whichever OpenSSL used on this host.
        $message = str_replace("\r\n", "\n", $smime);

        $headerEnd = strpos($message, "\n\n");
        if ($headerEnd === false) {
            throw new RuntimeException('Unexpected S/MIME output while signing the pass.');
        }

        $headers = substr($message, 0, $headerEnd);
        $body = substr($message, $headerEnd + 2);

        if (! preg_match('/boundary="?([^";\n]+)"?/i', $headers, $match)) {
            // Single-part: the body is the signature itself.
            if (stripos($headers, 'pkcs7-signature') !== false) {
                return $this->decodeSignature($body);
            }

            throw new RuntimeException('No signature part in the S/MIME output.');
        }

        foreach (explode('--' . $match[1], $body) as $part) {
            $split = strpos($part, "\n\n");
            if ($split === false) {
                continue;
            }

            if (stripos(substr($part, 0, $split), 'pkcs7-signature') === false) {
                continue;
            }

            return $this->decodeSignature(substr($part, $split + 2));
        }

        throw new RuntimeException('No signature part in the S/MIME output.');
    }

    private function decodeSignature(string $base64): string
    {
        // MIME wraps base64 at 64 characters and the part is followed by the
        // boundary; strip everything that is not alphabet before decoding.
        $clean = preg_replace('/[^A-Za-z0-9+\/=]/', '', $base64) ?? '';
        $der = base64_decode($clean, true);

        if ($der === false || $der === '') {
            throw new RuntimeException('Could not decode the pass signature.');
        }

        return $der;
    }

    /**
     * @param  array<string,string>  $files
     */
    private function zip(array $files): string
    {
        $path = tempnam(sys_get_temp_dir(), 'pkpass_');

        try {
            $zip = new ZipArchive();
            // OVERWRITE because tempnam already created the (empty) file, and
            // ZipArchive::CREATE alone would treat it as a corrupt archive.
            if ($zip->open($path, ZipArchive::OVERWRITE | ZipArchive::CREATE) !== true) {
                throw new RuntimeException('Could not create the pass archive.');
            }

            foreach ($files as $name => $contents) {
                $zip->addFromString($name, $contents);
            }
            $zip->close();

            return (string) file_get_contents($path);
        } finally {
            @unlink($path);
        }
    }
}
