<?php
// migrations/add_available_balance_to_users.php

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

try {
    echo "Starting migration: Add available_balance to users table...\n";
    
    // Check if column already exists
    $hasColumn = DB::schema()->hasColumn('users', 'available_balance');
    
    if ($hasColumn) {
        echo "Column 'available_balance' already exists. Skipping...\n";
    } else {
        // Add available_balance column
        DB::schema()->table('users', function ($table) {
            $table->decimal('available_balance', 15, 2)->default(0)->after('password');
        });
        
        echo "✓ Successfully added 'available_balance' column to users table\n";
        echo "Migration completed successfully!\n";
    }
    
} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
