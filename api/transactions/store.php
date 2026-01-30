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

    // Validate: Cannot deposit to a completed goal
    if ((float)$goal->current_amount >= (float)$goal->target_amount) {
        Response::error('This goal has already been completed and cannot accept more deposits.', 400);
    }

    // Validate method based on goal type
    // If goal type is undefined/null (old goals), default to 'digital' constraint or allow loose?
    // Let's assume default is 'digital' from migration.
    // However, the model doesn't automatically cast enum default on read unless DB enforces it.
    // DB default is set.
    
    $goalType = $goal->type ?? 'digital'; // Fallback
    
    if ($goalType === 'cash' && $method !== 'manual') {
        Response::error('Cash Goal can only be filled manually (Cash).', 400);
    }
    
    if ($goalType === 'digital' && $method === 'manual') {
        Response::error('Digital Goal cannot be filled manually. Use E-Wallet or Account Balance.', 400);
    }
    
    // Create transaction
    $transaction = Transaction::create([
        'goal_id' => $goalId,
        'amount' => $amount,
        'method' => $method,
        'description' => $description,
        'transaction_date' => \Carbon\Carbon::now()
    ]);

    // Handle Balance Deduction if method is 'balance'
    if ($method === 'balance') {
        $user = \App\Models\User::find($userId);
        if ($user->available_balance < $amount) {
            // Rollback (delete transaction) if insufficient
            $transaction->delete();
            Response::error('Insufficient available balance. You have Rp ' . number_format($user->available_balance, 0, ',', '.'), 400);
        }
        $user->subtractAvailableBalance($amount);
    }
    
    // Create Notification
    try {
        if ($transaction) {
            \App\Models\Notification::createNotification(
                $userId,
                'Deposit Successful',
                'A deposit of Rp ' . number_format($amount, 0, ',', '.') . ' has been successfully added via ' . ucfirst($method) . '.',
                'deposit'
            );
        }
    } catch (\Exception $e) {
        // Ignore notification errors
    }
    
    // Update goal amount and get overflow info
    $result = $goal->addAmount($amount);
    
    // Safety: Automatically add overflow to Available Balance
    if ($result['overflow_amount'] > 0) {
        $user = \App\Models\User::find($userId);
        $user->addAvailableBalance($result['overflow_amount']);
    }
    
    // Update notification message if goal completed
    $notificationMessage = 'A deposit of Rp ' . number_format($amount, 0, ',', '.') . ' has been successfully added via ' . ucfirst($method) . '.';
    if ($result['completed']) {
        $notificationMessage = 'Congratulations! The goal "' . $goal->name . '" has been reached! A deposit of Rp ' . number_format($result['deposited_amount'], 0, ',', '.') . ' was added.';
        if ($result['overflow_amount'] > 0) {
            $notificationMessage .= ' You have a remaining balance of Rp ' . number_format($result['overflow_amount'], 0, ',', '.') . ' to be allocated.';
        }
    }
    
    // Create Notification
    try {
        if ($transaction) {
            \App\Models\Notification::createNotification(
                $userId,
                $result['completed'] ? 'Goal Reached!' : 'Deposit Successful',
                $notificationMessage,
                'deposit'
            );
        }
    } catch (\Exception $e) {
        // Ignore notification errors
    }
    
    Response::success('Transaction created successfully', [
        'id' => $transaction->id,
        'goal_id' => $transaction->goal_id,
        'amount' => (float) $transaction->amount,
        'method' => $transaction->method,
        'description' => $transaction->description,
        'transaction_date' => $transaction->transaction_date->toDateTimeString(),
        'created_at' => $transaction->created_at->toDateTimeString(),
        'goal_completed' => $result['completed'],
        'deposited_amount' => (float) $result['deposited_amount'],
        'overflow_amount' => (float) $result['overflow_amount']
    ], 201);
    
} catch (Exception $e) {
    Response::error('Failed to create transaction: ' . $e->getMessage(), 500);
}
?>
