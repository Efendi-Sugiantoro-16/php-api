<?php
require_once __DIR__ . '/bootstrap.php';
use Illuminate\Database\Capsule\Manager as Capsule;

$tables = Capsule::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");

foreach ($tables as $table) {
    $tableName = $table->table_name;
    echo "Table: $tableName\n";
    $columns = Capsule::select("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = ?", [$tableName]);
    foreach ($columns as $column) {
        echo "  - {$column->column_name} ({$column->data_type})\n";
    }
    echo "\n";
}
?>
