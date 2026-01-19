<?php
// test_deposit_overflow.php

require_once __DIR__ . '/bootstrap.php';

use App\Models\User;
use App\Models\Goal;
use App\Models\Transaction;

echo "=== Test Deposit with Overflow ===\n\n";

try {
    // Find or create test user
    $user = User::where('email', 'test@example.com')->first();
    if (!$user) {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        echo "✓ Created test user\n";
    } else {
        echo "✓ Using existing test user\n";
    }
    
    // Create test goals
    echo "\n--- Creating Test Goals ---\n";
    
    // Goal 1: Macbook - Target 15 juta
    $goalMacbook = Goal::create([
        'user_id' => $user->id,
        'name' => 'Macbook',
        'target_amount' => 15000000,
        'current_amount' => 0,
        'deadline' => date('Y-m-d', strtotime('+6 months')),
        'description' => 'Target tabungan Macbook'
    ]);
    echo "✓ Goal 'Macbook' created (Target: Rp 15.000.000)\n";
    
    // Goal 2: HP - Target 5 juta
    $goalHP = Goal::create([
        'user_id' => $user->id,
        'name' => 'HP',
        'target_amount' => 5000000,
        'current_amount' => 0,
        'deadline' => date('Y-m-d', strtotime('+3 months')),
        'description' => 'Target tabungan HP'
    ]);
    echo "✓ Goal 'HP' created (Target: Rp 5.000.000)\n";
    
    // Test 1: Deposit normal (tidak overflow)
    echo "\n--- Test 1: Deposit Normal (Rp 10 juta) ---\n";
    $transaction1 = Transaction::create([
        'goal_id' => $goalMacbook->id,
        'amount' => 10000000,
        'method' => 'bank_transfer',
        'description' => 'Test deposit normal',
        'transaction_date' => \Carbon\Carbon::now()
    ]);
    
    $result1 = $goalMacbook->addAmount(10000000);
    echo "Deposited: Rp " . number_format($result1['deposited_amount'], 0, ',', '.') . "\n";
    echo "Overflow: Rp " . number_format($result1['overflow_amount'], 0, ',', '.') . "\n";
    echo "Goal Completed: " . ($result1['completed'] ? 'Yes' : 'No') . "\n";
    echo "Current Amount: Rp " . number_format($goalMacbook->current_amount, 0, ',', '.') . "\n";
    
    // Test 2: Deposit dengan overflow
    echo "\n--- Test 2: Deposit dengan Overflow (Rp 15 juta, overflow Rp 10 juta) ---\n";
    $transaction2 = Transaction::create([
        'goal_id' => $goalMacbook->id,
        'amount' => 15000000,
        'method' => 'bank_transfer',
        'description' => 'Test deposit overflow',
        'transaction_date' => \Carbon\Carbon::now()
    ]);
    
    // Refresh goal
    $goalMacbook = Goal::find($goalMacbook->id);
    $result2 = $goalMacbook->addAmount(15000000);
    
    echo "Deposited: Rp " . number_format($result2['deposited_amount'], 0, ',', '.') . "\n";
    echo "Overflow: Rp " . number_format($result2['overflow_amount'], 0, ',', '.') . "\n";
    echo "Goal Completed: " . ($result2['completed'] ? 'Yes' : 'No') . "\n";
    echo "Current Amount: Rp " . number_format($goalMacbook->current_amount, 0, ',', '.') . "\n";
    
    if ($result2['overflow_amount'] > 0) {
        echo "\n⚠️  OVERFLOW DETECTED! Rp " . number_format($result2['overflow_amount'], 0, ',', '.') . " perlu dialokasikan.\n";
    }
    
    // Test 3: Deposit ke goal yang sudah completed
    echo "\n--- Test 3: Deposit ke Goal yang Sudah Completed ---\n";
    $goalMacbook = Goal::find($goalMacbook->id);
    $result3 = $goalMacbook->addAmount(5000000);
    
    echo "Deposited: Rp " . number_format($result3['deposited_amount'], 0, ',', '.') . "\n";
    echo "Overflow: Rp " . number_format($result3['overflow_amount'], 0, ',', '.') . "\n";
    echo "Goal Completed: " . ($result3['completed'] ? 'Yes' : 'No') . "\n";
    
    echo "\n✓ All tests completed successfully!\n";
    
    // Cleanup
    echo "\n--- Cleanup ---\n";
    $goalMacbook->delete();
    $goalHP->delete();
    $user->delete();
    echo "✓ Test data cleaned up\n";
    
} catch (Exception $e) {
    echo "\n✗ Test failed: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
?>
