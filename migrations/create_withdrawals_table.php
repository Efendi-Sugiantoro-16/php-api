<?php
// migrations/create_withdrawals_table.php
// Run this file to create the withdrawals table

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

echo "=== Creating Withdrawals Table ===\n\n";

try {
    $schema = Capsule::schema();
    
    // Check if table already exists
    if ($schema->hasTable('withdrawals')) {
        echo "Table 'withdrawals' already exists. Skipping...\n";
    } else {
        // Create withdrawals table
        $schema->create('withdrawals', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->decimal('amount', 15, 2);
            $table->string('method', 50)->nullable();
            $table->string('account_number', 50)->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id', 'idx_withdrawals_user_id');
            $table->index('status', 'idx_withdrawals_status');
        });
        
        echo "✅ Table 'withdrawals' created successfully!\n";
    }
    
    echo "\n=== Migration Complete ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
