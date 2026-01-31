<?php
require_once __DIR__ . '/bootstrap.php';
use Illuminate\Database\Capsule\Manager as Capsule;

try {
    $columns = Capsule::select("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'users'");
    foreach ($columns as $column) {
        echo $column->column_name . " (" . $column->data_type . ")\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
