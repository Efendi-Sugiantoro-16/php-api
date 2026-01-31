<?php
require_once __DIR__ . '/bootstrap.php';
use App\Models\Goal;

$goalId = 10;
$goal = Goal::find($goalId);

if ($goal) {
    echo "Goal ID: " . $goal->id . "\n";
    echo "Type Value: [" . ($goal->type ?? 'NULL') . "]\n";
    echo "Type Type: " . gettype($goal->type) . "\n";
} else {
    echo "Goal $goalId not found\n";
}
