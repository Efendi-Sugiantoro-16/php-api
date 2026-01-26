<?php
// api/goals/store.php

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Goal;
use App\Middleware\Auth;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

$userId = Auth::authenticate();
$data = Response::getJsonInput();

// Validate input
Response::validateRequiredFields($data, ['name', 'target_amount']);

$name = trim($data['name']);
$targetAmount = (float) $data['target_amount'];
$deadline = isset($data['deadline']) ? $data['deadline'] : null;
$description = isset($data['description']) ? trim($data['description']) : null;
$type = isset($data['type']) && in_array($data['type'], ['cash', 'digital']) ? $data['type'] : 'digital';

// Validate target amount
if ($targetAmount <= 0) {
    Response::error('Target amount must be greater than 0', 400);
}

try {
    $goal = Goal::create([
        'user_id' => $userId,
        'name' => $name,
        'target_amount' => $targetAmount,
        'deadline' => $deadline,
        'description' => $description,
        'type' => $type
    ]);
    
    Response::success('Goal created successfully', [
        'id' => $goal->id,
        'name' => $goal->name,
        'target_amount' => (float) $goal->target_amount,
        'current_amount' => (float) $goal->current_amount,
        'deadline' => $goal->deadline ? $goal->deadline->format('Y-m-d') : null,
        'description' => $goal->description,
        'type' => $goal->type,
        'created_at' => $goal->created_at->toDateTimeString()
    ], 201);
    
} catch (Exception $e) {
    Response::error('Failed to create goal: ' . $e->getMessage(), 500);
}
?>
