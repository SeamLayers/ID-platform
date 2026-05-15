<?php

namespace App\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;

class CardCodeService
{
    /**
     * Generate QR code image (base64 or file)
     */
    public function generateQr(string $url)
    {
//        return base64_encode(
//            QrCode::format('png')
//                ->size(300)
//                ->errorCorrection('H')
//                ->generate($url)
//        );
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

        $fullUrl = url('/card/' . $publicUrl);

        return [
            'public_url' => $publicUrl,

            'qr_code' => $this->generateQr($fullUrl),

            'nfc_code' => $this->generateNfcCode($employee),
        ];
    }
}
