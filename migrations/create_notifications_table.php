<?php
// migrations/create_notifications_table.php

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

echo "=== Creating 'notifications' table ===\n\n";

try {
    if (!Capsule::schema()->hasTable('notifications')) {
        Capsule::schema()->create('notifications', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('title', 100);
            $table->text('message');
            $table->string('type', 50)->default('info'); // 'deposit', 'withdrawal', 'info'
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
            $table->index('is_read');
        });
        echo "✅ Table 'notifications' created successfully.\n";
    } else {
        echo "⚠️ Table 'notifications' already exists.\n";
    }
    
    echo "\n=== Migration Complete ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
