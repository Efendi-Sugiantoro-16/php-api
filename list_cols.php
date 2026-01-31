<?php
require_once __DIR__ . '/bootstrap.php';
use Illuminate\Database\Capsule\Manager as Capsule;

try {
    $columns = Capsule::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'user_badges'");
    foreach ($columns as $column) {
        echo $column->column_name . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
