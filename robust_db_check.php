<?php
require_once __DIR__ . '/bootstrap.php';
use Illuminate\Database\Capsule\Manager as Capsule;

try {
    echo "Check connection...\n";
    $result = Capsule::select("SELECT 1");
    echo "Connection OK\n";
    
    echo "Checking users table...\n";
    $columns = Capsule::select("
        SELECT column_name, data_type 
        FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'users'
    ");
    
    if (empty($columns)) {
        echo "Table 'users' NOT FOUND!\n";
    } else {
        echo "Table 'users' columns:\n";
        foreach ($columns as $col) {
            echo "- " . $col->column_name . " (" . $col->data_type . ")\n";
        }
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
}
