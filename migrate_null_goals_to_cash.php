<?php
// migrate_null_goals_to_cash.php
// This script updates all goals with NULL type to 'cash' as default
// Run this once to migrate legacy data

require_once __DIR__ . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

try {
    echo "Starting migration: NULL goals -> cash goals\n";
    echo "==========================================\n\n";
    
    // Get all goals with NULL type
    $nullGoals = DB::table('goals')
                    ->whereNull('type')
                    ->get();
    
    $count = count($nullGoals);
    echo "Found {$count} goals with NULL type\n";
    
    if ($count > 0) {
        echo "\nGoals to be updated:\n";
        foreach ($nullGoals as $goal) {
            echo "  - ID: {$goal->id}, Name: {$goal->name}\n";
        }
        
        echo "\nUpdating...\n";
        
        // Update all NULL types to 'cash'
        $updated = DB::table('goals')
                     ->whereNull('type')
                     ->update(['type' => 'cash']);
        
        echo "✓ Successfully updated {$updated} goals to type='cash'\n";
        echo "\nMigration completed successfully!\n";
    } else {
        echo "No goals to update. All goals already have a type.\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: Migration failed!\n";
    echo "Error message: " . $e->getMessage() . "\n";
    exit(1);
}
?>
