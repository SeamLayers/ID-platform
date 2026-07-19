<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;

class CardCodeService
{
    /**
     * Generate QR code image (base64 or file)
     */



    /**
     * Render the card's QR to the public disk and return its path.
     *
     * PNG needs the imagick extension. Plenty of shared hosts don't have it,
     * and this used to take the whole request down with
     * "You need to install the imagick extension to use this back end" — which
     * meant issuing a card 500'd, and (once cards are auto-created with the
     * employee) an employee could be created with no card at all.
     *
     * So: PNG when we can, SVG when we can't — the pure-PHP backend always
     * works — and null rather than an exception if even that fails. `qr_code`
     * is nullable and clients branch on the file extension.
     */
    public function generateQr(string $url, string $employeeNumber): ?string
    {
        foreach (['png', 'svg'] as $format) {
            try {
                $image = QrCode::format($format)
                    ->size(300)
                    ->errorCorrection('H')
                    ->generate($url);

                $path = sprintf(
                    'qr-codes/QR_%s_%s.%s',
                    $employeeNumber,
                    Carbon::now()->format('Ymd_His'),
                    $format
                );

                Storage::disk('public')->put($path, $image);

                return $path;
            } catch (\Throwable $e) {
                Log::warning("QR generation as {$format} failed: " . $e->getMessage());
            }
        }

        return null;
    }
    /**
     * Generate NFC code (logical identifier)
     */
    public function generateNfcCode($employee): string
    {
        return 'NFC-' . $employee->employee_number . '-' . Str::random(6);
    }




    /**
     * Generate public URL slug
     */
    public function generatePublicUrl(): string
    {
        return Str::random(40);
    }

    public function generateAll($employee): array
    {
        $publicUrl = $this->generatePublicUrl();

        $fullUrl = url('/api/v1/card/' . $publicUrl);

        return [
            'public_url' => $publicUrl,
            // ?src=qr so a scan of this image is attributed to QR in the
            // dashboard's source mix rather than counted as a plain link visit.
            'qr_code'    => $this->generateQr($fullUrl . '?src=qr', $employee->employee_number),
            'nfc_code'   => $this->generateNfcCode($employee),
        ];
    }
}
