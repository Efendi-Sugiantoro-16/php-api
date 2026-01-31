<?php
require_once __DIR__ . '/bootstrap.php';
use Illuminate\Database\Capsule\Manager as Capsule;

echo "--- Checking Goals Table Columns ---\n";
$columns = Capsule::schema()->getColumnListing('goals');
print_r($columns);

if (in_array('type', $columns)) {
    echo "Column 'type' EXISTS.\n";
} else {
    echo "Column 'type' MISSING.\n";
}
