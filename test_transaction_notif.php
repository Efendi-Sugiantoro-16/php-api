<?php
// test_transaction_notif.php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/config/cors.php'; // For constants if needed

use App\Models\User;
use App\Models\Goal;
use App\Models\Notification;
use App\Models\Transaction;

echo "=== TRANSACTION NOTIFICATION TEST ===\n";

try {
    // 1. Setup User & Goal
    $user = User::first();
    if (!$user) {
        die("No user found. Run migration/seeds first.\n");
    }
    
    // Ensure user has balance for balance test
    $user->update(['available_balance' => 1000000]);
    
    echo "User: " . $user->name . " (ID: " . $user->id . ")\n";
    
    $goal = Goal::create([
        'user_id' => $user->id,
        'name' => 'Auto Notif Test ' . time(),
        'target_amount' => 500000,
        'current_amount' => 0,
        'type' => 'digital'
    ]);
    
    echo "Goal Created: " . $goal->name . " (ID: " . $goal->id . ")\n";

    // 2. Simulate POST /transactions/store logic
    // We can't easily call the API endpoint file directly since it handles HTTP input.
    // So we replicate the logic or call a helper if we refactored it.
    // For now, we'll manually call the logic to see if Notification::createNotification works in this context.
    
    echo "\n--- Simulating Transaction ---\n";
    $amount = 50000;
    $method = 'wallet';
    
    $transaction = Transaction::create([
        'goal_id' => $goal->id,
        'amount' => $amount,
        'method' => $method,
        'transaction_date' => \Carbon\Carbon::now()
    ]);
    
    echo "Transaction Created ID: " . $transaction->id . "\n";
    
    // This is the part we wan to test: Notification Creation
    echo "Attempting to create notification...\n";
    
    $notif = Notification::createNotification(
        $user->id,
        'Tabungan Berhasil',
        'Tabungan sebesar Rp ' . number_format($amount, 0, ',', '.') . ' berhasil ditambahkan via ' . ucfirst($method) . '.',
        'deposit'
    );
    
    if ($notif) {
        echo "✅ Notification Created ID: " . $notif->id . "\n";
        echo "   Title: " . $notif->title . "\n";
    } else {
        echo "❌ Notification Creation Failed (Returned null)\n";
    }

} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
}
?>
