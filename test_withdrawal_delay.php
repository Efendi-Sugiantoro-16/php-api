<?php
// test_withdrawal_delay.php
require_once __DIR__ . '/bootstrap.php';
use App\Models\User;
use App\Models\Withdrawal;
use App\Models\Goal;
use Carbon\Carbon;

echo "=== DELAY LOGIC TEST ===\n";

// 1. Setup
$user = User::first();
if (!$user) die("No user");

$goal = Goal::create([
    'user_id' => $user->id,
    'name' => 'Delay Test',
    'target_amount' => 100000,
    'current_amount' => 100000
]);

// 2. Create Pending Withdrawal
$w = Withdrawal::create([
    'user_id' => $user->id,
    'goal_id' => $goal->id,
    'amount' => 10000,
    'method' => 'dana',
    'status' => 'pending'
]);

echo "Created Withdrawal ID: " . $w->id . " at " . Carbon::now()->toTimeString() . "\n";

// 3. Test Immediate Process (Should fail)
echo "attempting process immediately...\n";
$count = Withdrawal::processDelayedApprovals($user->id);
if ($count === 0) {
    echo "✅ Correctly ignored (too early)\n";
} else {
    echo "❌ ERROR: Processed too early!\n";
}

// 4. Sleep 6 seconds
echo "Sleeping 6 seconds...\n";
sleep(6);

// 5. Test Process after 5s (Should succeed)
echo "attempting process after 6s...\n";
$count = Withdrawal::processDelayedApprovals($user->id);
if ($count > 0) {
    echo "✅ Successfully processed after delay.\n";
} else {
    echo "❌ ERROR: Not processed after delay. Query probably failed.\n";
    // Debug info
    $w->refresh();
    dump($w->created_at);
    dump(Carbon::now()->subSeconds(5));
}
?>
