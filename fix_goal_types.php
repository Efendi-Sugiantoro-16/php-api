<?php
require_once __DIR__ . '/bootstrap.php';
use App\Models\Goal;

echo "--- Fixing Null Goal Types ---\n";

$nullGoals = Goal::whereNull('type')->get();
echo "Found " . $nullGoals->count() . " goals with null type.\n";

foreach ($nullGoals as $goal) {
    $goal->type = 'digital'; // Default to digital
    $goal->save();
    echo "Updated Goal ID {$goal->id}: {$goal->name} to 'digital'\n";
}

echo "Done.\n";
