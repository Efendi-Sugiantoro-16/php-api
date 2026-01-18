<?php
// test_fcm_verbose.php
// Diagnostic script untuk melihat SELURUH proses pengiriman notifikasi ke Firebase
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/bootstrap.php';

echo "======================================\n";
echo "  FCM VERBOSE DIAGNOSTIC TEST\n";
echo "======================================\n\n";

// --- STEP 1: CREDENTIAL CHECK ---
echo "[1] CREDENTIAL CHECK\n";
$envCred = getenv('FIREBASE_SERVICE_ACCOUNT');
$filePath = __DIR__ . '/service-account.json';

if ($envCred) {
    echo "    ✅ ENV 'FIREBASE_SERVICE_ACCOUNT' ditemukan.\n";
    $serviceAccount = json_decode($envCred, true);
} elseif (file_exists($filePath)) {
    echo "    ✅ File 'service-account.json' ditemukan.\n";
    $serviceAccount = json_decode(file_get_contents($filePath), true);
} else {
    echo "    ❌ TIDAK ADA CREDENTIAL! Script berhenti.\n";
    exit(1);
}

echo "    Project ID: " . ($serviceAccount['project_id'] ?? 'N/A') . "\n";
echo "    Client Email: " . ($serviceAccount['client_email'] ?? 'N/A') . "\n\n";

// --- STEP 2: JWT GENERATION ---
echo "[2] JWT GENERATION\n";
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
$signResult = openssl_sign($signatureInput, $signature, $serviceAccount['private_key'], 'SHA256');

if ($signResult) {
    echo "    ✅ OpenSSL Sign berhasil.\n";
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    $jwt = $signatureInput . "." . $base64UrlSignature;
    echo "    JWT (truncated): " . substr($jwt, 0, 50) . "...\n\n";
} else {
    echo "    ❌ OpenSSL Sign GAGAL!\n";
    exit(1);
}

// --- STEP 3: GET ACCESS TOKEN ---
echo "[3] EXCHANGE JWT FOR ACCESS TOKEN\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
    'assertion' => $jwt
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$tokenResponse = curl_exec($ch);
$tokenHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "    HTTP Code: $tokenHttpCode\n";
$tokenData = json_decode($tokenResponse, true);

if (isset($tokenData['access_token'])) {
    echo "    ✅ Access Token diterima!\n";
    $accessToken = $tokenData['access_token'];
    echo "    Token (truncated): " . substr($accessToken, 0, 50) . "...\n\n";
} else {
    echo "    ❌ GAGAL dapat Access Token!\n";
    echo "    Response: " . $tokenResponse . "\n";
    exit(1);
}

// --- STEP 4: SEND TEST MESSAGE ---
echo "[4] SEND TEST MESSAGE TO FCM\n";
$projectId = $serviceAccount['project_id'];
$url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

// Ambil user ID pertama untuk test
$user = \App\Models\User::first();
$topic = $user ? 'user_' . $user->id : 'test_topic';

echo "    Target Topic: $topic\n";
echo "    Target URL: $url\n";

$message = [
    'message' => [
        'topic' => $topic,
        'notification' => [
            'title' => '🔔 Test Notifikasi',
            'body' => 'Ini adalah pesan test dari backend PHP. Waktu: ' . date('H:i:s')
        ],
        'data' => [
            'type' => 'test',
            'timestamp' => (string) time()
        ]
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

$sendResponse = curl_exec($ch);
$sendHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "    HTTP Code: $sendHttpCode\n";
echo "    Response: " . $sendResponse . "\n\n";

if ($sendHttpCode >= 200 && $sendHttpCode < 300) {
    echo "======================================\n";
    echo "  ✅ SUKSES! Pesan terkirim ke Firebase.\n";
    echo "======================================\n\n";
    echo "Jika notifikasi tetap tidak muncul di HP, maka masalahnya ada di:\n";
    echo "1. Flutter belum subscribe ke topic '$topic'\n";
    echo "2. google-services.json tidak sesuai dengan project Firebase\n";
    echo "3. Package name Android tidak terdaftar di Firebase\n";
} else {
    echo "======================================\n";
    echo "  ❌ GAGAL! Pesan TIDAK terkirim.\n";
    echo "======================================\n";
}
?>
