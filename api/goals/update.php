<?php
// api/goals/update.php

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Goal;
use App\Middleware\Auth;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    Response::error('Method not allowed', 405);
}

$userId = Auth::authenticate();
$data = Response::getJsonInput();

// Validate input
Response::validateRequiredFields($data, ['id', 'name', 'target_amount']);

$goalId = (int) $data['id'];
$name = trim($data['name']);
$targetAmount = (float) $data['target_amount'];
$deadline = isset($data['deadline']) ? $data['deadline'] : null;
$description = isset($data['description']) ? trim($data['description']) : null;

// Validate target amount
if ($targetAmount <= 0) {
    Response::error('Target amount must be greater than 0', 400);
}

try {
    // Find goal and check ownership
    $goal = Goal::where('id', $goalId)
                ->where('user_id', $userId)
                ->first();
    
    if (!$goal) {
        Response::error('Goal not found or access denied', 404);
    }
    
    // Update goal
    $goal->update([
        'name' => $name,
        'target_amount' => $targetAmount,
        'deadline' => $deadline,
        'description' => $description
    ]);
    
    Response::success('Goal updated successfully', [
        'id' => $goal->id,
        'name' => $goal->name,
        'target_amount' => (float) $goal->target_amount,
        'current_amount' => (float) $goal->current_amount,
        'deadline' => $goal->deadline ? $goal->deadline->format('Y-m-d') : null,
        'description' => $goal->description,
        'updated_at' => $goal->updated_at->toDateTimeString()
    ]);
    
} catch (Exception $e) {
    Response::error('Failed to update goal: ' . $e->getMessage(), 500);
}
?>
