<?php
// api/transactions/index.php

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Goal;
use App\Models\Transaction;
use App\Middleware\Auth;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

$userId = Auth::authenticate();

// Get goal_id from query parameter
$goalId = isset($_GET['goal_id']) ? (int) $_GET['goal_id'] : null;

if (!$goalId) {
    Response::error('goal_id parameter is required', 400);
}

try {
    // Check if goal belongs to user
    $goal = Goal::where('id', $goalId)
        ->where('user_id', $userId)
        ->first();

    if (!$goal) {
        Response::error('Goal not found or access denied', 404);
    }

    // Get transactions
    $transactions = Transaction::where('goal_id', $goalId)
        ->orderBy('transaction_date', 'desc')
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'goal_id' => $transaction->goal_id,
                'amount' => (float) $transaction->amount,
                'description' => $transaction->description,
                'transaction_date' => $transaction->transaction_date->toDateTimeString(),
                'created_at' => $transaction->created_at->toDateTimeString()
            ];
        });

    Response::success('Transactions retrieved successfully', $transactions->toArray());

} catch (Exception $e) {
    Response::error('Failed to retrieve transactions: ' . $e->getMessage(), 500);
}
?>