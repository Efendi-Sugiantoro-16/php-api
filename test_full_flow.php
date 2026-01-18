<?php
// Test script for full flow: Register -> Login -> Profile -> Create Goal -> Add Transaction -> Dashboard

function request($url, $method, $data = [], $token = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if (!empty($data)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['code' => $httpCode, 'body' => json_decode($result, true)];
}

$baseUrl = 'http://localhost:8001/api';
$email = 'user_' . time() . '@example.com';
$password = 'password123';

echo "1. Registering user ($email)...\n";
$res = request($baseUrl . '/auth/register', 'POST', [
    'name' => 'Dashboard Tester',
    'email' => $email,
    'password' => $password
]);
echo "   Status: " . $res['code'] . "\n";

if ($res['code'] !== 201) die("Registration failed.\n");

echo "2. Logging in...\n";
$res = request($baseUrl . '/auth/login', 'POST', [
    'email' => $email,
    'password' => $password
]);
echo "   Status: " . $res['code'] . "\n";

if ($res['code'] !== 200) die("Login failed.\n");
$token = $res['body']['data']['token'];
echo "   Token obtained.\n";

echo "3. Getting Profile...\n";
$res = request($baseUrl . '/profile/user', 'GET', [], $token);
echo "   Status: " . $res['code'] . "\n";
echo "   User: " . $res['body']['data']['name'] . "\n";

echo "4. Creating Goal (Laptop)...\n";
$res = request($baseUrl . '/goals/store', 'POST', [
    'name' => 'Laptop',
    'target_amount' => 10000000,
    'description' => 'New macbook'
], $token);
echo "   Status: " . $res['code'] . "\n";
if ($res['code'] !== 201) {
    $msg = isset($res['body']['message']) ? $res['body']['message'] : json_encode($res['body']);
    echo "   Error: " . $msg . "\n";
    die("Goal creation failed.\n");
}
$goalId = $res['body']['data']['id'];

echo "5. Adding Transaction (500000)...\n";
$res = request($baseUrl . '/transactions/store', 'POST', [
    'goal_id' => $goalId,
    'amount' => 500000,
    'description' => 'First saving'
], $token);
echo "   Status: " . $res['code'] . "\n";
if ($res['code'] !== 201) {
    echo "   Error: " . json_encode($res['body']) . "\n";
    die("Transaction creation failed.\n");
}

echo "6. Getting Dashboard Summary...\n";
$res = request($baseUrl . '/dashboard/summary', 'GET', [], $token);
echo "   Status: " . $res['code'] . "\n";
if ($res['code'] !== 200) {
    echo "   Error: " . json_encode($res['body']) . "\n";
    die("Dashboard failed.\n");
}
$summary = $res['body']['data'];
echo "   Total Goals: " . $summary['total_goals'] . "\n";
echo "   Total Saved: " . $summary['total_saved'] . "\n";
echo "   Completed: " . $summary['completed_goals'] . "\n";

?>
