<?php
// test_notif_debug.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/bootstrap.php';
use App\Models\Withdrawal;
use App\Models\Notification;
use App\Models\User;
use App\Models\Goal;
use Carbon\Carbon;

echo "=== DEBUG NOTIFICATION ===\n";

// 1. Create Dummy User & Goal
$email = 'debug_notif_' . time() . '@test.com';
$user = User::create([
    'name' => 'Debug Notif',
    'email' => $email,
    'password' => password_hash('123', PASSWORD_BCRYPT)
]);
echo "User ID: " . $user->id . "\n";

$goal = Goal::create([
    'user_id' => $user->id,
    'name' => 'Debug Goal',
    'target_amount' => 1000000,
    'current_amount' => 500000
]);

// 2. Create OLD Pending Withdrawal (1 hour ago)
$w = Withdrawal::create([
    'user_id' => $user->id,
    'amount' => 50000,
    'method' => 'dana',
    'status' => 'pending'
]);
// Force query update to bypass Eloquent timestamp handling
Illuminate\Database\Capsule\Manager::table('withdrawals')
    ->where('id', $w->id)
    ->update(['created_at' => Carbon::now()->subHours(2)]);

echo "Created Pending Withdrawal ID: " . $w->id . "\n";

// 3. Trigger Auto Approval
echo "Running processDelayedApprovals...\n";
$count = Withdrawal::processDelayedApprovals($user->id);
echo "Processed Count: $count\n";

// 4. Check Status
$w->refresh();
echo "New Status: " . $w->status . "\n";

// 5. Check Notification
$notifs = Illuminate\Database\Capsule\Manager::table('notifications')
    ->where('user_id', $user->id)
    ->get();

echo "Notifications Found: " . $notifs->count() . "\n";
foreach ($notifs as $n) {
    echo " - [" . $n->type . "] " . $n->title . ": " . $n->message . "\n";
}

if ($notifs->count() == 0) {
    echo "❌ ERROR: No notification created!\n";
} else {
    echo "✅ SUCCESS: Notification created.\n";
}
?>
