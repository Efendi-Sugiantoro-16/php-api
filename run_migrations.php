<?php
// run_migrations.php
require_once __DIR__ . '/bootstrap.php';

$migrations = [
    'create_tables.php',
    'create_withdrawals_table.php',
    'create_notifications_table.php',
    'create_badges_tables.php',
    'add_available_balance_to_users.php',
    'add_goal_id_to_withdrawals.php',
    'add_method_to_transactions.php',
    'add_type_to_goals.php'
];

foreach ($migrations as $migration) {
    $path = __DIR__ . '/migrations/' . $migration;
    if (file_exists($path)) {
        echo "Running migration: $migration...\n";
        try {
            // We use a separate process or capture output by requiring? 
            // The migration files have 'require_once bootstrap.php', so requiring them here is fine.
            include $path;
            echo "----------------------------------------\n";
        } catch (Exception $e) {
            echo "Error running migration $migration: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Migration file not found: $migration\n";
    }
}
?>
