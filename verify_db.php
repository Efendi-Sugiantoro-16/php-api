<?php
require_once __DIR__ . '/bootstrap.php';
use Illuminate\Database\Capsule\Manager as Capsule;

try {
    echo "Connecting to database...\n";
    $tables = Capsule::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
    echo "Tables found:\n";
    foreach ($tables as $table) {
        echo "- " . $table->table_name . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
