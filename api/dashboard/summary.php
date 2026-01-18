<?php
// api/dashboard/summary.php

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
    // Get all goals for this user
    $goals = Goal::where('user_id', $userId)->get();
    
    // Calculate statistics
    $totalGoals = $goals->count();
    $totalSaved = $goals->sum('current_amount');
    $totalTarget = $goals->sum('target_amount');
    $completedGoals = $goals->filter(function($goal) {
        return $goal->current_amount >= $goal->target_amount;
    })->count();
    
    $activeGoals = $totalGoals - $completedGoals;
    $overallProgress = $totalTarget > 0 ? round(($totalSaved / $totalTarget) * 100, 2) : 0;
    
    // Get nearest goal to completion (highest percentage, not yet completed)
    $nearestGoal = $goals->filter(function($goal) {
                         return $goal->current_amount < $goal->target_amount;
                     })
                     ->sortByDesc(function($goal) {
                         return $goal->progress_percentage;
                     })
                     ->first();
    
    $nearestGoalData = null;
    if ($nearestGoal) {
        $nearestGoalData = [
            'id' => $nearestGoal->id,
            'name' => $nearestGoal->name,
            'target_amount' => (float) $nearestGoal->target_amount,
            'current_amount' => (float) $nearestGoal->current_amount,
            'deadline' => $nearestGoal->deadline ? $nearestGoal->deadline->format('Y-m-d') : null,
            'progress_percentage' => $nearestGoal->progress_percentage
        ];
    }
    
    $summary = [
        'total_goals' => $totalGoals,
        'total_saved' => (float) $totalSaved,
        'total_target' => (float) $totalTarget,
        'completed_goals' => $completedGoals,
        'active_goals' => $activeGoals,
        'overall_progress' => $overallProgress,
        'nearest_goal' => $nearestGoalData
    ];
    
    Response::success('Dashboard summary retrieved successfully', $summary);
    
} catch (Exception $e) {
    Response::error('Failed to retrieve dashboard summary: ' . $e->getMessage(), 500);
}
?>
