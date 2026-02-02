<?php


require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

echo "Running migrations...\n\n";

try {
    // Create users table
    if (!Capsule::schema()->hasTable('users')) {
        Capsule::schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('name', 100);
            $table->string('email', 100)->unique();
            $table->string('password', 255);
            $table->timestamps();
        });
        echo "✓ Table 'users' created\n";
    } else {
        echo "✓ Table 'users' already exists\n";
    }

    // Create goals table
    if (!Capsule::schema()->hasTable('goals')) {
        Capsule::schema()->create('goals', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('name', 100);
            $table->decimal('target_amount', 15, 2);
            $table->decimal('current_amount', 15, 2)->default(0);
            $table->date('deadline')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->index('user_id');
        });
        echo "✓ Table 'goals' created\n";
    } else {
        echo "✓ Table 'goals' already exists\n";
    }

    // Create transactions table
    if (!Capsule::schema()->hasTable('transactions')) {
        Capsule::schema()->create('transactions', function ($table) {
            $table->increments('id');
            $table->integer('goal_id')->unsigned();
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->timestamp('transaction_date')->useCurrent();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('goal_id')
                ->references('id')
                ->on('goals')
                ->onDelete('cascade');

            $table->index('goal_id');
        });
        echo "✓ Table 'transactions' created\n";
    } else {
        echo "✓ Table 'transactions' already exists\n";
    }

    // Create tokens table
    if (!Capsule::schema()->hasTable('tokens')) {
        Capsule::schema()->create('tokens', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('token', 255)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->index('token');
            $table->index('user_id');
        });
        echo "✓ Table 'tokens' created\n";
    } else {
        echo "✓ Table 'tokens' already exists\n";
    }

    echo "\n✅ All migrations completed successfully!\n";

} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>