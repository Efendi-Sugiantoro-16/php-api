<?php
// api/transactions/store.php

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Goal;
use App\Models\Transaction;
use App\Middleware\Auth;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

$userId = Auth::authenticate();
$data = Response::getJsonInput();

// Validate input
Response::validateRequiredFields($data, ['goal_id', 'amount']);

$goalId = (int) $data['goal_id'];
$amount = (float) $data['amount'];
$description = isset($data['description']) ? trim($data['description']) : null;
$method = isset($data['method']) ? strtolower(trim($data['method'])) : 'manual';

// Validate amount
if ($amount <= 0) {
    Response::error('Amount must be greater than 0', 400);
}

try {
    // Check if goal exists and belongs to user
    $goal = Goal::where('id', $goalId)
                ->where('user_id', $userId)
                ->first();
    
    if (!$goal) {
        Response::error('Goal not found or access denied', 404);
    }
    
    // Create transaction
    $transaction = Transaction::create([
        'goal_id' => $goalId,
        'amount' => $amount,
        'method' => $method,
        'description' => $description,
        'transaction_date' => \Carbon\Carbon::now()
    ]);
    
    // Create Notification
    try {
        if ($transaction) {
            \App\Models\Notification::createNotification(
                $userId,
                'Tabungan Berhasil',
                'Tabungan sebesar Rp ' . number_format($amount, 0, ',', '.') . ' berhasil ditambahkan via ' . ucfirst($method) . '.',
                'deposit'
            );
        }
    } catch (\Exception $e) {
        // Ignore notification errors
    }
    
    // Update goal amount
    $goal->addAmount($amount);
    
    Response::success('Transaction created successfully', [
        'id' => $transaction->id,
        'goal_id' => $transaction->goal_id,
        'amount' => (float) $transaction->amount,
        'method' => $transaction->method,
        'description' => $transaction->description,
        'transaction_date' => $transaction->transaction_date->toDateTimeString(),
        'created_at' => $transaction->created_at->toDateTimeString()
    ], 201);
    
} catch (Exception $e) {
    Response::error('Failed to create transaction: ' . $e->getMessage(), 500);
}
?>
