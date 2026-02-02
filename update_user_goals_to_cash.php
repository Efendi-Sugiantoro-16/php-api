<?php
// update_user_goals_to_cash.php
// Update specific user's goals to cash type (for testing/demo purposes)

require_once __DIR__ . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

// Change this to the user ID you want to update
$targetUserId = 5; // User ID from the debug output

try {
    echo "Updating goals for user ID: {$targetUserId}\n";
    echo "==========================================\n\n";

    // Get user's goals
    $goals = DB::table('goals')
        ->where('user_id', $targetUserId)
        ->get();

    echo "User has " . count($goals) . " goals:\n\n";

    foreach ($goals as $goal) {
        $typeDisplay = $goal->type === null ? 'NULL' : $goal->type;
        echo "  - ID: {$goal->id}, Name: {$goal->name}, Current Type: {$typeDisplay}\n";
    }

    echo "\n";
    echo "Do you want to update the first 2 goals to 'cash' type? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $input = trim(fgets($handle));
    fclose($handle);

    if (strtolower($input) !== 'yes' && strtolower($input) !== 'y') {
        echo "Operation cancelled.\n";
        exit(0);
    }

    // Update first 2 goals to cash
    $updated = DB::table('goals')
        ->where('user_id', $targetUserId)
        ->whereIn('id', [12, 10]) // IDs from debug output for "tes" and "Laptop"
        ->update(['type' => 'cash']);

    echo "\n✓ Successfully updated {$updated} goals to type='cash'\n";
    echo "\nUpdated goals:\n";

    $updatedGoals = DB::table('goals')
        ->where('user_id', $targetUserId)
        ->get();

    foreach ($updatedGoals as $goal) {
        echo "  - {$goal->name}: {$goal->type}\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>