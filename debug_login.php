<?php
require_once __DIR__ . '/bootstrap.php';
use App\Models\User;

try {
    $email = 'efendisugiantoro14@gmail.com';
    echo "Finding user: $email\n";
    $user = User::where('email', $email)->first();
    if ($user) {
        echo "User found: " . $user->name . "\n";
        echo "Password hash: " . $user->password . "\n";
    } else {
        echo "User not found\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
