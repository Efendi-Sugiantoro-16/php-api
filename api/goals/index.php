<?php
// api/goals/index.php

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Goal;
use App\Middleware\Auth;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

$userId = Auth::authenticate();

try {
    $goals = Goal::where('user_id', $userId)
                 ->orderBy('created_at', 'desc')
                 ->get()
                 ->map(function($goal) {
                     return [
                         'id' => $goal->id,
                         'name' => $goal->name,
                         'target_amount' => (float) $goal->target_amount,
                         'current_amount' => (float) $goal->current_amount,
                         'deadline' => $goal->deadline ? $goal->deadline->format('Y-m-d') : null,
                         'description' => $goal->description,
                         'created_at' => $goal->created_at->toDateTimeString(),
                         'progress_percentage' => $goal->progress_percentage
                     ];
                 });
    
    Response::success('Goals retrieved successfully', $goals->toArray());
    
} catch (Exception $e) {
    Response::error('Failed to retrieve goals: ' . $e->getMessage(), 500);
}
?>
