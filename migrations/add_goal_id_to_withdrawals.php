<?php
// migrations/add_goal_id_to_withdrawals.php
// Menambahkan kolom goal_id ke tabel withdrawals

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== MIGRATION: Add goal_id to withdrawals ===\n\n";

try {
    // Check if column exists
    $hasColumn = DB::schema()->hasColumn('withdrawals', 'goal_id');
    
    if ($hasColumn) {
        echo "Column 'goal_id' already exists. Skipping.\n";
    } else {
        // Add goal_id column (nullable for backward compatibility with existing data)
        DB::schema()->table('withdrawals', function ($table) {
            $table->unsignedBigInteger('goal_id')->nullable()->after('user_id');
            $table->foreign('goal_id')->references('id')->on('goals')->onDelete('set null');
        });
        
        echo "✅ Column 'goal_id' added successfully!\n";
    }
    
    echo "\n=== MIGRATION COMPLETE ===\n";
    
} catch (Exception $e) {
    echo "❌ Migration Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
