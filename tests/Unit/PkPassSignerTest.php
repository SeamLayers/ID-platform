<?php

namespace Tests\Unit;

use App\Services\Wallet\PkPassSigner;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

/**
 * The pass bundle is a format, not an opinion: iOS checks the manifest digests
 * and the detached signature before it will show the "Add" sheet, and reports
 * every failure as the same unhelpful "Safari cannot download this file".
 *
 * These tests build a real bundle with a throwaway self-signed certificate, so
 * the zip layout, the SHA-1 manifest and the DER conversion are all proven
 * without needing Apple's Pass Type ID certificate. What a real certificate
 * changes is *whose* signature it is — not how the bundle is assembled.
 */
class PkPassSignerTest extends TestCase
{
    private string $dir;
    private string $certificate;
    private string $wwdr;
    private string $password = 'test-pass';

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('openssl') || ! extension_loaded('zip')) {
            $this->markTestSkipped('ext-openssl and ext-zip are required to sign a pass.');
        }

        $this->dir = sys_get_temp_dir() . '/pkpass-test-' . bin2hex(random_bytes(4));
        mkdir($this->dir);

        $this->certificate = $this->dir . '/certificate.p12';
        $this->wwdr = $this->dir . '/wwdr.pem';

        [$certPem, $keyResource] = $this->selfSignedPair();

        // A PKCS#12 bundle is what Keychain Access exports, so the signer is
        // exercised on the same shape the real certificate arrives in.
        openssl_pkcs12_export_to_file(
            $certPem,
            $this->certificate,
            $keyResource,
            $this->password
        );

        // Stands in for Apple's WWDR intermediate: the signer only has to embed
        // whatever chain certificate it is given.
        file_put_contents($this->wwdr, $certPem);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->dir);

        parent::tearDown();
    }

    public function test_it_builds_a_zip_containing_the_pass_the_manifest_and_a_signature(): void
    {
        $bytes = $this->signer()->build([
            'pass.json' => '{"formatVersion":1}',
            'icon.png' => 'not-really-a-png-but-bytes-are-bytes',
        ]);

        $entries = $this->entries($bytes);

        $this->assertArrayHasKey('pass.json', $entries);
        $this->assertArrayHasKey('icon.png', $entries);
        $this->assertArrayHasKey('manifest.json', $entries);
        $this->assertArrayHasKey('signature', $entries);
    }

    public function test_the_manifest_holds_the_sha1_of_every_bundled_file(): void
    {
        $pass = '{"formatVersion":1,"serialNumber":"abc"}';
        $icon = random_bytes(64);

        $entries = $this->entries($this->signer()->build([
            'pass.json' => $pass,
            'icon.png' => $icon,
        ]));

        $manifest = json_decode($entries['manifest.json'], true);

        $this->assertSame(sha1($pass), $manifest['pass.json']);
        $this->assertSame(sha1($icon), $manifest['icon.png']);
        // The manifest describes the payload only — signing its own digest
        // would be circular, and iOS does not look for either entry.
        $this->assertArrayNotHasKey('manifest.json', $manifest);
        $this->assertArrayNotHasKey('signature', $manifest);
    }

    public function test_the_signature_is_der_not_the_smime_envelope_openssl_returns(): void
    {
        $entries = $this->entries($this->signer()->build([
            'pass.json' => '{"formatVersion":1}',
            'icon.png' => 'icon',
        ]));

        $signature = $entries['signature'];

        // DER starts with the SEQUENCE tag 0x30. If the MIME headers had been
        // left on, this would begin with "MIME-Version:" — the single most
        // common reason a hand-rolled pass is rejected with no explanation.
        $this->assertSame("\x30", $signature[0]);
        $this->assertStringNotContainsString('MIME-Version', $signature);
        $this->assertStringNotContainsString('Content-Type', $signature);
    }

    public function test_the_signature_verifies_as_a_detached_cms_over_the_manifest(): void
    {
        $openssl = trim((string) shell_exec('command -v openssl 2>/dev/null'));
        if ($openssl === '') {
            $this->markTestSkipped('The openssl CLI is needed to verify a detached signature.');
        }

        $entries = $this->entries($this->signer()->build([
            'pass.json' => '{"formatVersion":1}',
            'icon.png' => 'icon',
        ]));

        $signatureFile = $this->dir . '/signature.der';
        $manifestFile = $this->dir . '/manifest.json';
        file_put_contents($signatureFile, $entries['signature']);
        file_put_contents($manifestFile, $entries['manifest.json']);

        // The same question iOS asks: is this DER a detached CMS signature over
        // exactly these manifest bytes? -noverify skips the trust chain, which
        // is the one thing a self-signed test certificate cannot satisfy and
        // the one thing this class is not responsible for.
        $command = sprintf(
            '%s smime -verify -binary -inform DER -in %s -content %s -noverify -out /dev/null 2>&1',
            escapeshellcmd($openssl),
            escapeshellarg($signatureFile),
            escapeshellarg($manifestFile)
        );

        $output = [];
        $status = 0;
        exec($command, $output, $status);

        $this->assertSame(
            0,
            $status,
            'The detached signature did not verify: ' . implode(' ', $output)
        );
    }

    public function test_tampering_with_the_manifest_breaks_the_signature(): void
    {
        $openssl = trim((string) shell_exec('command -v openssl 2>/dev/null'));
        if ($openssl === '') {
            $this->markTestSkipped('The openssl CLI is needed to verify a detached signature.');
        }

        $entries = $this->entries($this->signer()->build([
            'pass.json' => '{"formatVersion":1}',
            'icon.png' => 'icon',
        ]));

        $signatureFile = $this->dir . '/signature.der';
        $manifestFile = $this->dir . '/manifest.json';
        file_put_contents($signatureFile, $entries['signature']);
        // One byte different — what an edited pass looks like.
        file_put_contents($manifestFile, $entries['manifest.json'] . ' ');

        exec(sprintf(
            '%s smime -verify -binary -inform DER -in %s -content %s -noverify -out /dev/null 2>&1',
            escapeshellcmd($openssl),
            escapeshellarg($signatureFile),
            escapeshellarg($manifestFile)
        ), $output, $status);

        $this->assertNotSame(0, $status, 'A tampered manifest still verified.');
    }

    public function test_it_refuses_a_bundle_with_no_icon(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/icon\.png/');

        $this->signer()->build(['pass.json' => '{}']);
    }

    public function test_it_refuses_a_bundle_with_no_pass_json(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/pass\.json/');

        $this->signer()->build(['icon.png' => 'icon']);
    }

    public function test_a_wrong_certificate_password_fails_loudly(): void
    {
        $signer = new PkPassSigner($this->certificate, 'not-the-password', $this->wwdr);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/certificate/i');

        $signer->build(['pass.json' => '{}', 'icon.png' => 'icon']);
    }

    private function signer(): PkPassSigner
    {
        return new PkPassSigner($this->certificate, $this->password, $this->wwdr);
    }

    /** @return array{0: string, 1: \OpenSSLAsymmetricKey} */
    private function selfSignedPair(): array
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $csr = openssl_csr_new(
            ['commonName' => 'iD+ Pass Test', 'countryName' => 'SA'],
            $key,
            ['digest_alg' => 'sha256']
        );

        $cert = openssl_csr_sign($csr, null, $key, 365, ['digest_alg' => 'sha256']);
        openssl_x509_export($cert, $pem);

        return [$pem, $key];
    }

    /** @return array<string,string> */
    private function entries(string $pkpass): array
    {
        $path = $this->dir . '/bundle.pkpass';
        file_put_contents($path, $pkpass);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true, 'The pass is not a readable zip.');

        $files = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $files[$name] = $zip->getFromIndex($i);
        }
        $zip->close();

        return $files;
    }
}
