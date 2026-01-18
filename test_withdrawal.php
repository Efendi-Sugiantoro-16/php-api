<?php
// Test script for Withdrawal endpoints

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
$email = 'withdrawal_test_' . time() . '@example.com';
$password = 'password123';

echo "=== WITHDRAWAL ENDPOINTS TEST ===\n\n";

// 1. Register
echo "1. Registering user ($email)...\n";
$res = request($baseUrl . '/auth/register', 'POST', [
    'name' => 'Withdrawal Tester',
    'email' => $email,
    'password' => $password
]);
echo "   Status: " . $res['code'] . "\n";
if ($res['code'] !== 201) die("   ❌ Registration failed.\n");
echo "   ✅ User registered\n\n";

// 2. Login
echo "2. Logging in...\n";
$res = request($baseUrl . '/auth/login', 'POST', [
    'email' => $email,
    'password' => $password
]);
echo "   Status: " . $res['code'] . "\n";
if ($res['code'] !== 200) die("   ❌ Login failed.\n");
$token = $res['body']['data']['token'];
echo "   ✅ Token obtained\n\n";

// 3. Create Goal with balance
echo "3. Creating Goal (Tabungan)...\n";
$res = request($baseUrl . '/goals/store', 'POST', [
    'name' => 'Tabungan Test',
    'target_amount' => 1000000,
    'description' => 'Test goal for withdrawal'
], $token);
echo "   Status: " . $res['code'] . "\n";
if ($res['code'] !== 201) die("   ❌ Goal creation failed: " . json_encode($res['body']) . "\n");
$goalId = $res['body']['data']['id'];
echo "   ✅ Goal created (ID: $goalId)\n\n";

// 4. Add Transaction (deposit)
echo "4. Adding Transaction (Rp 500.000)...\n";
$res = request($baseUrl . '/transactions/store', 'POST', [
    'goal_id' => $goalId,
    'amount' => 500000,
    'description' => 'Deposit for withdrawal test'
], $token);
echo "   Status: " . $res['code'] . "\n";
if ($res['code'] !== 201) die("   ❌ Transaction failed: " . json_encode($res['body']) . "\n");
echo "   ✅ Transaction added\n\n";

// 5. Test withdrawal request (should succeed)
echo "5. Request Withdrawal (Rp 100.000 via Dana)...\n";
$res = request($baseUrl . '/withdrawals/request', 'POST', [
    'amount' => 100000,
    'method' => 'dana',
    'account_number' => '08123456789',
    'notes' => 'Test withdrawal'
], $token);
echo "   Status: " . $res['code'] . "\n";
if ($res['code'] !== 201) {
    echo "   ❌ Withdrawal request failed: " . json_encode($res['body']) . "\n";
} else {
    $withdrawalId = $res['body']['data']['id'];
    echo "   ✅ Withdrawal requested (ID: $withdrawalId)\n";
    echo "   Balance: Rp " . number_format($res['body']['data']['total_balance'], 0, ',', '.') . "\n\n";
}

// 6. Test withdrawal request exceeding balance (should fail)
echo "6. Request Withdrawal exceeding balance (Rp 1.000.000)...\n";
$res = request($baseUrl . '/withdrawals/request', 'POST', [
    'amount' => 1000000,
    'method' => 'gopay',
    'account_number' => '08987654321'
], $token);
echo "   Status: " . $res['code'] . "\n";
if ($res['code'] === 400) {
    echo "   ✅ Correctly rejected: " . $res['body']['message'] . "\n\n";
} else {
    echo "   ❌ Should have been rejected!\n\n";
}

// 7. Get withdrawal history
echo "7. Get Withdrawal History...\n";
$res = request($baseUrl . '/withdrawals/index', 'GET', [], $token);
echo "   Status: " . $res['code'] . "\n";
if ($res['code'] !== 200) {
    echo "   ❌ Failed: " . json_encode($res['body']) . "\n";
} else {
    echo "   ✅ History retrieved\n";
    echo "   Total requests: " . $res['body']['data']['summary']['total_requests'] . "\n";
    echo "   Pending: " . $res['body']['data']['summary']['pending_count'] . "\n\n";
}

// 8. Filter by status
echo "8. Get Pending Withdrawals only...\n";
$res = request($baseUrl . '/withdrawals/index?status=pending', 'GET', [], $token);
echo "   Status: " . $res['code'] . "\n";
if ($res['code'] === 200) {
    echo "   ✅ Filtered results: " . count($res['body']['data']['withdrawals']) . " pending\n\n";
}

// 9. Approve withdrawal
echo "9. Approve Withdrawal (ID: $withdrawalId)...\n";
$res = request($baseUrl . '/withdrawals/approve', 'POST', [
    'withdrawal_id' => $withdrawalId,
    'action' => 'approve',
    'notes' => 'Test approval'
], $token);
echo "   Status: " . $res['code'] . "\n";
if ($res['code'] !== 200) {
    echo "   ❌ Approval failed: " . json_encode($res['body']) . "\n";
} else {
    echo "   ✅ Approved! Status: " . $res['body']['data']['status'] . "\n\n";
}

// 10. Check balance after approval
echo "10. Checking Dashboard after withdrawal...\n";
$res = request($baseUrl . '/dashboard/summary', 'GET', [], $token);
echo "   Status: " . $res['code'] . "\n";
if ($res['code'] === 200) {
    echo "   ✅ Total Saved: Rp " . number_format($res['body']['data']['total_saved'], 0, ',', '.') . "\n";
    echo "   (Should be 400.000 after 100.000 withdrawal)\n\n";
}

echo "=== ALL TESTS COMPLETED ===\n";
?>
