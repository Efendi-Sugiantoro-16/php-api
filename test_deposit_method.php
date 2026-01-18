<?php
// test_deposit_method.php

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
$email = 'deposit_method_test_' . time() . '@example.com';
$password = 'password123';

echo "=== DEPOSIT METHOD TEST ===\n\n";

// 1. Register & Login
echo "1. Registering user...\n";
$res = request($baseUrl . '/auth/register', 'POST', [
    'name' => 'Deposit Tester',
    'email' => $email,
    'password' => $password
]);
if ($res['code'] !== 201) die("Registration failed.\n");

$res = request($baseUrl . '/auth/login', 'POST', [
    'email' => $email,
    'password' => $password
]);
$token = $res['body']['data']['token'];
echo "   ✅ Logged in\n\n";

// 2. Create Goal
echo "2. Creating Goal...\n";
$res = request($baseUrl . '/goals/store', 'POST', [
    'name' => 'Deposit Test Goal',
    'target_amount' => 5000000
], $token);
$goalId = $res['body']['data']['id'];
echo "   ✅ Goal Created (ID: $goalId)\n\n";

// 3. Deposit via Dana
echo "3. Deposit Rp 100.000 via DANA...\n";
$res = request($baseUrl . '/transactions/store', 'POST', [
    'goal_id' => $goalId,
    'amount' => 100000,
    'method' => 'dana',
    'description' => 'Topup via Dana'
], $token);

echo "   Status: " . $res['code'] . "\n";
if ($res['code'] === 201) {
    echo "   Method: " . $res['body']['data']['method'] . "\n";
    if ($res['body']['data']['method'] === 'dana') {
        echo "   ✅ Method correctly saved as 'dana'\n";
    } else {
        echo "   ❌ Method MISMATCH\n";
    }
} else {
    echo "   ❌ Failed: " . json_encode($res['body']) . "\n";
}

// 4. Deposit via GoPay
echo "\n4. Deposit Rp 50.000 via GOPAY...\n";
$res = request($baseUrl . '/transactions/store', 'POST', [
    'goal_id' => $goalId,
    'amount' => 50000,
    'method' => 'gopay',
    'description' => 'Topup via GoPay'
], $token);

if ($res['code'] === 201 && $res['body']['data']['method'] === 'gopay') {
    echo "   ✅ Method correctly saved as 'gopay'\n";
} else {
    echo "   ❌ Failed or Mismatch\n";
}

// 5. Deposit Default (Manual)
echo "\n5. Deposit Rp 25.000 (No method specified)...\n";
$res = request($baseUrl . '/transactions/store', 'POST', [
    'goal_id' => $goalId,
    'amount' => 25000
], $token);

if ($res['code'] === 201 && $res['body']['data']['method'] === 'manual') {
    echo "   ✅ Default method is 'manual'\n";
} else {
    echo "   ❌ Failed or Mismatch (Got: " . ($res['body']['data']['method'] ?? 'null') . ")\n";
}

echo "\n=== TEST COMPLETED ===\n";
?>
