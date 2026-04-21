<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    private function accessToken()
    {
        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];

        // Hardcoded path to the JSON file
        $credentialsPath = base_path('storage/firebase/firebase_credentials.json');

        // Check if it exists and is a file
        if (!is_file($credentialsPath)) {
            Log::error("Firebase credentials file not found at: $credentialsPath");
            return null; // handle gracefully
        }

        // Read credentials
        $credentialsArray = json_decode(file_get_contents($credentialsPath), true);

        $credentials = new ServiceAccountCredentials($scopes, $credentialsArray);

        $credentials->fetchAuthToken();

        return $credentials->getLastReceivedToken()['access_token'] ?? null;
    }

    public function sendToDevice($deviceToken, $title, $body, array $data = [])
    {
        $token = $this->accessToken();
        if (!$token) {
            Log::error("Cannot send Firebase notification: missing access token.");
            return null;
        }

        $url = 'https://fcm.googleapis.com/v1/projects/' . 'YOUR_FIREBASE_PROJECT_ID' . '/messages:send';

        $payload = [
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'data' => $data
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_SSL_VERIFYPEER => true, // ignore SSL (dev only)
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            Log::error("Firebase cURL Error: $error");
            return null;
        }

        curl_close($ch);

        return json_decode($response, true);
    }
}
