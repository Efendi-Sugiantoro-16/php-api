<?php
require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

try {
    Capsule::schema()->table('goals', function ($table) {
        if (!Capsule::schema()->hasColumn('goals', 'type')) {
            $table->enum('type', ['digital', 'cash'])->default('digital')->after('description');
            echo "Column 'type' added to 'goals' table.\n";
        } else {
            echo "Column 'type' already exists in 'goals' table.\n";
        }
    });
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>