<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class SmsService
{

    public static function sendSMS($phoneNumber, $message): bool
    {
        try {

            $response = Http::withoutVerifying()
                ->timeout(10)
                ->asForm()
                ->post(config('integration.oursms.url'), [
                    'token' => config('integration.oursms.token'),
                    'src'   => config('integration.oursms.src'),
                    'dests' => $phoneNumber,
                    'body'  => $message,
                ]);

            if (!$response->successful()) {
                Log::error('SMS Failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
            }

//            dd($response->body());
            if (!$response->successful()) {
                Log::error('SMS API HTTP Error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return false;
            }

            $responseBody = $response->body();

            // ⚠️ هنا تعتمد على شكل response من OurSMS
            if (str_contains($responseBody, 'OK') || str_contains($responseBody, 'SUCCESS')) {
                return true;
            }

            // Duplicate handling (مثل اللي كنت بتعمله سابقًا)
            if (str_contains($responseBody, 'duplicate')) {
                return true;
            }

            Log::warning('SMS API Logical Failure', [
                'response' => $responseBody,
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('SMS Exception', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
