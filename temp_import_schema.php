<?php
// temp_import_schema.php
require_once __DIR__ . '/bootstrap.php';
use Illuminate\Database\Capsule\Manager as Capsule;

try {
    $pdo = Capsule::connection()->getPdo();
    $sql = file_get_contents(__DIR__ . '/database_schema.sql');
    
    // Split by semicolon but be careful with functions if any
    // For this schema it looks simple enough.
    $pdo->exec($sql);
    echo "Schema imported successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
