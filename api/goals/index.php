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
$search = $_GET['search'] ?? null;
$month = $_GET['month'] ?? null;
$year = $_GET['year'] ?? null;

try {
    $query = Goal::where('user_id', $userId);

    // Search by name
    if ($search) {
        $query->where('name', 'LIKE', "%{$search}%");
    }

    // Filter by Month
    if ($month) {
        $query->whereMonth('created_at', $month);
    }

    // Filter by Year
    if ($year) {
        $query->whereYear('created_at', $year);
    }

    $goals = $query->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($goal) {
            return [
                'id' => $goal->id,
                'name' => $goal->name,
                'target_amount' => (float) $goal->target_amount,
                'current_amount' => (float) $goal->current_amount,
                'deadline' => $goal->deadline ? $goal->deadline->format('Y-m-d') : null,
                'description' => $goal->description,
                'type' => $goal->type, // Add type
                'created_at' => $goal->created_at->toDateTimeString(),
                'progress_percentage' => $goal->progress_percentage
            ];
        });

    Response::success('Goals retrieved successfully', $goals->toArray());

} catch (Exception $e) {
    Response::error('Failed to retrieve goals: ' . $e->getMessage(), 500);
}
?>