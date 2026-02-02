<?php
// api/goals/forecast.php

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Goal;
use App\Models\Transaction;
use App\Middleware\Auth;
use App\Helpers\Response;
use Carbon\Carbon;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

$userId = Auth::authenticate();
$goalId = $_GET['goal_id'] ?? null;

try {
    $query = Goal::where('user_id', $userId);

    if ($goalId) {
        $query->where('id', $goalId);
    }

    $goals = $query->get();
    $forecasts = [];

    foreach ($goals as $goal) {
        // Get transactions for this goal
        $transactions = Transaction::where('goal_id', $goal->id)
            ->orderBy('transaction_date', 'asc')
            ->get();

        $totalSaved = (float) $goal->current_amount;
        $targetAmount = (float) $goal->target_amount;
        $remainingAmount = max(0, $targetAmount - $totalSaved);

        if ($goal->isCompleted()) {
            $forecasts[] = [
                'goal_id' => $goal->id,
                'goal_name' => $goal->name,
                'status' => 'completed',
                'description' => 'Goal sudah selesai! 🎉',
                'predicted_completion_date' => $goal->updated_at->format('Y-m-d'),
                'days_to_complete' => 0
            ];
            continue;
        }

        // Calculate Average Savings per Day
        $firstTransactionDate = $transactions->first() ? $transactions->first()->transaction_date : $goal->created_at;
        $daysActive = max(1, $firstTransactionDate->diffInDays(Carbon::now()));
        $avgDailySavings = $totalSaved / $daysActive;

        // If no savings yet, we can't predict much
        if ($avgDailySavings <= 0) {
            $forecasts[] = [
                'goal_id' => $goal->id,
                'goal_name' => $goal->name,
                'status' => 'too_early',
                'description' => 'Ayo mulai menabung untuk melihat prediksi selesainya!',
                'predicted_completion_date' => null,
                'days_to_complete' => null
            ];
            continue;
        }

        $daysToComplete = ceil($remainingAmount / $avgDailySavings);
        $predictedDate = Carbon::now()->addDays($daysToComplete);

        // Determine Status based on Deadline
        $status = 'on_track';
        $description = 'Kamu dalam jalur yang benar!';

        if ($goal->deadline) {
            $deadline = Carbon::parse($goal->deadline);
            $daysToDeadline = Carbon::now()->diffInDays($deadline, false);

            if ($daysToDeadline < 0) {
                $status = 'overdue';
                $description = 'Deadline sudah terlewati. Ayo kejar targetmu!';
            } elseif ($predictedDate->gt($deadline)) {
                $status = 'falling_behind';
                $description = 'Prediksi menunjukkan kamu akan sedikit terlambat dari deadline.';
            } elseif ($daysToComplete < ($daysToDeadline * 0.7)) {
                $status = 'ahead_of_schedule';
                $description = 'Luar biasa! Kamu menabung lebih cepat dari perkiraan.';
            }
        }

        $forecasts[] = [
            'goal_id' => $goal->id,
            'goal_name' => $goal->name,
            'status' => $status,
            'description' => $description,
            'predicted_completion_date' => $predictedDate->format('Y-m-d'),
            'days_to_complete' => $daysToComplete,
            'average_daily_savings' => round($avgDailySavings, 2),
            'remaining_amount' => $remainingAmount,
            'current_progress' => $goal->progress_percentage
        ];
    }

    Response::success('Forecast generated successfully', $forecasts);

} catch (Exception $e) {
    Response::error('Failed to generate forecast: ' . $e->getMessage(), 500);
}
?>