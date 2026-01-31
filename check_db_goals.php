<?php
// check_db_goals.php
require_once __DIR__ . '/bootstrap.php';
use App\Models\Goal;

try {
    $latest = Goal::orderBy('id', 'desc')->take(5)->get();
    echo "Latest 5 Goals:\n";
    foreach ($latest as $g) {
        echo "ID: {$g->id}, Name: {$g->name}, User ID: {$g->user_id}, Created: {$g->created_at}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
