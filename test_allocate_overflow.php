<?php
// test_allocate_overflow.php

require_once __DIR__ . '/bootstrap.php';

use App\Models\User;
use App\Models\Goal;
use App\Models\Transaction;

echo "=== Test Manual Allocation of Overflow ===\n\n";

try {
    // Find or create test user
    $user = User::where('email', 'test@example.com')->first();
    if (!$user) {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'available_balance' => 0
        ]);
        echo "✓ Created test user\n";
    } else {
        echo "✓ Using existing test user\n";
        $user->available_balance = 0;
        $user->save();
    }
    
    echo "Initial Available Balance: Rp " . number_format($user->available_balance, 0, ',', '.') . "\n";
    
    // Create test goals
    echo "\n--- Creating Test Goals ---\n";
    
    $goalMacbook = Goal::create([
        'user_id' => $user->id,
        'name' => 'Macbook',
        'target_amount' => 15000000,
        'current_amount' => 0,
        'deadline' => date('Y-m-d', strtotime('+6 months')),
        'description' => 'Target tabungan Macbook'
    ]);
    echo "✓ Goal 'Macbook' created (Target: Rp 15.000.000)\n";
    
    $goalHP = Goal::create([
        'user_id' => $user->id,
        'name' => 'HP',
        'target_amount' => 5000000,
        'current_amount' => 0,
        'deadline' => date('Y-m-d', strtotime('+3 months')),
        'description' => 'Target tabungan HP'
    ]);
    echo "✓ Goal 'HP' created (Target: Rp 5.000.000)\n";
    
    // Simulate deposit dengan overflow
    echo "\n--- Simulating Deposit Rp 25 juta ke Goal Macbook ---\n";
    $transaction = Transaction::create([
        'goal_id' => $goalMacbook->id,
        'amount' => 25000000,
        'method' => 'bank_transfer',
        'description' => 'Deposit with overflow',
        'transaction_date' => \Carbon\Carbon::now()
    ]);
    
    $result = $goalMacbook->addAmount(25000000);
    echo "Deposited to Macbook: Rp " . number_format($result['deposited_amount'], 0, ',', '.') . "\n";
    echo "Overflow Amount: Rp " . number_format($result['overflow_amount'], 0, ',', '.') . "\n";
    echo "Goal Macbook Completed: " . ($result['completed'] ? 'Yes' : 'No') . "\n";
    
    $overflowAmount = $result['overflow_amount'];
    
    // Test Allocation 1: Allocate Rp 5 juta ke Goal HP
    echo "\n--- Test 1: Allocate Rp 5 juta ke Goal HP ---\n";
    
    $allocationTransaction = Transaction::create([
        'goal_id' => $goalHP->id,
        'amount' => 5000000,
        'method' => 'allocation',
        'description' => 'Alokasi dari overflow',
        'transaction_date' => \Carbon\Carbon::now()
    ]);
    
    $allocationResult = $goalHP->addAmount(5000000);
    echo "Allocated to HP: Rp " . number_format(5000000, 0, ',', '.') . "\n";
    echo "Goal HP Completed: " . ($allocationResult['completed'] ? 'Yes' : 'No') . "\n";
    echo "HP Current Amount: Rp " . number_format($goalHP->current_amount, 0, ',', '.') . "\n";
    
    $remainingAfterAllocation = $overflowAmount - 5000000;
    echo "Remaining after allocation: Rp " . number_format($remainingAfterAllocation, 0, ',', '.') . "\n";
    
    // Test Allocation 2: Save remaining to available balance
    echo "\n--- Test 2: Save Remaining Rp 5 juta to Available Balance ---\n";
    $user->addAvailableBalance($remainingAfterAllocation);
    $user = User::find($user->id); // Refresh
    echo "User Available Balance: Rp " . number_format($user->available_balance, 0, ',', '.') . "\n";
    
    // Verify results
    echo "\n--- Final Summary ---\n";
    $goalMacbook = Goal::find($goalMacbook->id);
    $goalHP = Goal::find($goalHP->id);
    $user = User::find($user->id);
    
    echo "Goal Macbook - Current: Rp " . number_format($goalMacbook->current_amount, 0, ',', '.') . " / Target: Rp " . number_format($goalMacbook->target_amount, 0, ',', '.') . " - " . ($goalMacbook->isCompleted() ? 'Completed ✓' : 'In Progress') . "\n";
    echo "Goal HP - Current: Rp " . number_format($goalHP->current_amount, 0, ',', '.') . " / Target: Rp " . number_format($goalHP->target_amount, 0, ',', '.') . " - " . ($goalHP->isCompleted() ? 'Completed ✓' : 'In Progress') . "\n";
    echo "User Available Balance: Rp " . number_format($user->available_balance, 0, ',', '.') . "\n";
    
    $totalAllocated = $goalMacbook->current_amount + $goalHP->current_amount + $user->available_balance;
    echo "\nTotal from Rp 25 juta deposit: Rp " . number_format($totalAllocated, 0, ',', '.') . "\n";
    
    if ($totalAllocated == 25000000) {
        echo "✓ All funds correctly allocated!\n";
    } else {
        echo "✗ Mismatch in allocation!\n";
    }
    
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
