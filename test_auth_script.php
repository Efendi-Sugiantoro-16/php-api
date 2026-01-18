<?php
// Simple test script using curl in PHP

function test_endpoint($url, $method, $data = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    if (!empty($data)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['code' => $httpCode, 'body' => $result];
}

$baseUrl = 'http://localhost:8001/api';

echo "Testing Register...\n";
$registerData = [
    'name' => 'Test Verifier',
    'email' => 'verifier_' . time() . '@example.com',
    'password' => 'password123'
];
echo "Registering user: " . $registerData['email'] . "\n";
$res = test_endpoint($baseUrl . '/auth/register', 'POST', $registerData);
echo "Status: " . $res['code'] . "\n";
echo "Body: " . $res['body'] . "\n\n";

if ($res['code'] == 201 || $res['code'] == 409) {
    echo "Testing Login...\n";
    $loginData = [
        'email' => $registerData['email'],
        'password' => 'password123'
    ];
    $resLogin = test_endpoint($baseUrl . '/auth/login', 'POST', $loginData);
    echo "Status: " . $resLogin['code'] . "\n";
    echo "Body: " . $resLogin['body'] . "\n";
} else {
    echo "Skipping login test due to register failure.\n";
}
?>
