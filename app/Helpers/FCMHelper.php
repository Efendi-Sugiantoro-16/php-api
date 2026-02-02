<?php
// app/Helpers/FCMHelper.php

namespace App\Helpers;

class FCMHelper
{
    private static $serviceAccountPath = __DIR__ . '/../../service-account.json';
    private static $accessToken = null;
    private static $tokenExpiry = 0;

    // Get Access Token using Service Account (Pure PHP - No Composer)
    private static function getAccessToken()
    {
        if (self::$accessToken && time() < self::$tokenExpiry) {
            return self::$accessToken;
        }

        $serviceAccount = null;

        // 1. Try Loading from Environment Variable (For Production/Railway)
        if (getenv('FIREBASE_SERVICE_ACCOUNT')) {
            $json = getenv('FIREBASE_SERVICE_ACCOUNT');
            $serviceAccount = json_decode($json, true);
        }

        // 2. Try Loading from File (For Local Development)
        if (!$serviceAccount && file_exists(self::$serviceAccountPath)) {
            $serviceAccount = json_decode(file_get_contents(self::$serviceAccountPath), true);
        }

        if (!$serviceAccount) {
            error_log("FCM Error: Service account credentials not found (Env/File).");
            return null;
        }

        $now = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $payload = [
            'iss' => $serviceAccount['client_email'],
            'sub' => $serviceAccount['client_email'],
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging'
        ];

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($header)));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));

        $signatureInput = $base64UrlHeader . "." . $base64UrlPayload;
        $signature = '';
        if (!openssl_sign($signatureInput, $signature, $serviceAccount['private_key'], 'SHA256')) {
            error_log("FCM Error: OpenSSL sign failed.");
            return null;
        }
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        $jwt = $signatureInput . "." . $base64UrlSignature;

        // Exchange JWT for Access Token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        if (isset($data['access_token'])) {
            self::$accessToken = $data['access_token'];
            self::$tokenExpiry = time() + $data['expires_in'] - 60;
            return self::$accessToken;
        }

        error_log("FCM Error: Failed to get access token. " . $response);
        return null;
    }

    // Send Notification
    public static function sendToTopic($topic, $title, $body, $data = [])
    {
        $accessToken = self::getAccessToken();
        if (!$accessToken)
            return false;

        // Extract Project ID (Hack: read from token payload or reload using same logic)
        // Better: Use a property. For now, let's reload safely.
        $projectId = '';
        if (getenv('FIREBASE_SERVICE_ACCOUNT')) {
            $cred = json_decode(getenv('FIREBASE_SERVICE_ACCOUNT'), true);
            $projectId = $cred['project_id'];
        } elseif (file_exists(self::$serviceAccountPath)) {
            $cred = json_decode(file_get_contents(self::$serviceAccountPath), true);
            $projectId = $cred['project_id'];
        }

        if (empty($projectId))
            return false;

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $message = [
            'message' => [
                'topic' => $topic,
                'notification' => [
                    'title' => $title,
                    'body' => $body
                ],
                'data' => $data // Data payload for app logic
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }

        error_log("FCM Send Error ($httpCode): " . $result);
        return false;
    }
}
?>