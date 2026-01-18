<?php
// migrations/add_method_to_transactions.php

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

echo "=== Adding 'method' column to 'transactions' table ===\n\n";

try {
    Capsule::schema()->table('transactions', function ($table) {
        if (!Capsule::schema()->hasColumn('transactions', 'method')) {
            $table->string('method', 50)->default('manual')->after('amount');
            echo "✅ Column 'method' added successfully.\n";
        } else {
            echo "⚠️ Column 'method' already exists.\n";
        }
    });
    
    echo "\n=== Migration Complete ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
