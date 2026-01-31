<?php
// debug_goal_types.php
// Debug script to check actual goal type values in database

require_once __DIR__ . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

try {
    echo "Checking goal types in database\n";
    echo "================================\n\n";
    
    // Get all goals with their type values
    $goals = DB::table('goals')
                ->select('id', 'name', 'type', 'user_id')
                ->get();
    
    echo "Total goals: " . count($goals) . "\n\n";
    
    $stats = [
        'null_value' => 0,
        'empty_string' => 0,
        'cash' => 0,
        'digital' => 0,
        'other' => 0
    ];
    
    foreach ($goals as $goal) {
        $typeDisplay = $goal->type === null ? 'NULL' : "'{$goal->type}'";
        echo "ID: {$goal->id} | Name: {$goal->name} | Type: {$typeDisplay} | User: {$goal->user_id}\n";
        
        // Count statistics
        if ($goal->type === null) {
            $stats['null_value']++;
        } elseif ($goal->type === '') {
            $stats['empty_string']++;
        } elseif ($goal->type === 'cash') {
            $stats['cash']++;
        } elseif ($goal->type === 'digital') {
            $stats['digital']++;
        } else {
            $stats['other']++;
        }
    }
    
    echo "\n================================\n";
    echo "Statistics:\n";
    echo "  NULL values: {$stats['null_value']}\n";
    echo "  Empty strings: {$stats['empty_string']}\n";
    echo "  Cash: {$stats['cash']}\n";
    echo "  Digital: {$stats['digital']}\n";
    echo "  Other: {$stats['other']}\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
