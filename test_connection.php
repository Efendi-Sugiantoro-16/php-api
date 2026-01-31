<?php
// test_connection.php
require_once __DIR__ . '/bootstrap.php';
use App\Models\User;

try {
    $count = User::count();
    echo "Connection Successful! Current user count: " . $count . "\n";
} catch (Exception $e) {
    echo "Connection Failed: " . $e->getMessage() . "\n";
}
?>
