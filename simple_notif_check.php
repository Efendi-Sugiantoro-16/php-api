<?php
require_once __DIR__ . '/bootstrap.php';
use App\Models\Notification;
use App\Models\User;

echo "=== SIMPLE NOTIF CHECK ===\n";

try {
    // defaults to first user or create one
    $user = User::first();
    if (!$user) {
        $user = User::create([
            'name' => 'Test User', 
            'email' => 'test@test.com', 
            'password' => '123'
        ]);
    }
    
    echo "Using User ID: " . $user->id . "\n";
    
    $notif = Notification::create([
        'user_id' => $user->id,
        'title' => 'Test Notification',
        'message' => 'This is a test message ' . time(),
        'type' => 'info'
    ]);
    
    echo "Created Notification ID: " . $notif->id . "\n";
    echo "✅ Database Insert Success\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
