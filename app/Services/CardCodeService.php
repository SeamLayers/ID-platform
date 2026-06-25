<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;

class CardCodeService
{
    /**
     * Generate QR code image (base64 or file)
     */



    public function generateQr(string $url, string $employeeNumber): string
    {
        $png = QrCode::format('png')
            ->size(300)
            ->errorCorrection('H')
            ->generate($url);

        $fileName = sprintf(
            'QR_%s_%s.png',
            $employeeNumber,
            Carbon::now()->format('Ymd_His')
        );

        $path = "qr-codes/{$fileName}";

        Storage::disk('public')->put($path, $png);

        return $path;
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

        $fullUrl = url('/api/card/' . $publicUrl);

        return [
            'public_url' => $publicUrl,
            'qr_code'    => $this->generateQr($fullUrl, $employee->employee_number),
            'nfc_code'   => $this->generateNfcCode($employee),
        ];
    }
}
