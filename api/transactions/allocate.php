<?php
// api/transactions/allocate.php

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Goal;
use App\Models\Transaction;
use App\Models\User;
use App\Middleware\Auth;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

$userId = Auth::authenticate();
$data = Response::getJsonInput();

// Validate input
Response::validateRequiredFields($data, ['allocations']);

$allocations = $data['allocations'];
$saveRemainingAsBalance = isset($data['save_remaining_as_balance']) ? $data['save_remaining_as_balance'] : false;

// Validate allocations is array
if (!is_array($allocations)) {
    Response::error('Allocations must be an array', 400);
}

// Allow empty allocations ONLY IF save_remaining_as_balance is true
// This covers the case where user wants to save ALL overflow to balance/withdraw
if (empty($allocations) && !$saveRemainingAsBalance) {
    Response::error('Allocations cannot be empty unless saving remaining as balance', 400);
}

try {
    $user = User::find($userId);
    if (!$user) {
        Response::error('User not found', 404);
    }
    
    $allocatedResults = [];
    $totalAllocated = 0;
    
    // Calculate total needed
    foreach ($allocations as $allocation) {
        $totalAllocated += (float) ($allocation['amount'] ?? 0);
    }
    
    // Deduct from available balance first (since it was added in store.php)
    if ($totalAllocated > 0) {
        if ($user->available_balance < $totalAllocated) {
             Response::error('Insufficient available balance to allocate Rp ' . number_format($totalAllocated, 0, ',', '.'), 400);
        }
        $user->subtractAvailableBalance($totalAllocated);
    }
    
    // Process each allocation
    foreach ($allocations as $allocation) {
        // Validate allocation structure
        if (!isset($allocation['goal_id']) || !isset($allocation['amount'])) {
            Response::error('Each allocation must have goal_id and amount', 400);
        }
        
        $goalId = (int) $allocation['goal_id'];
        $amount = (float) $allocation['amount'];
        
        // Validate amount
        if ($amount <= 0) {
            Response::error('Allocation amount must be greater than 0', 400);
        }
        
        // Check if goal exists and belongs to user
        $goal = Goal::where('id', $goalId)
                    ->where('user_id', $userId)
                    ->first();
        
        if (!$goal) {
            Response::error('Goal with ID ' . $goalId . ' not found or access denied', 404);
        }
        
        // Check if allocation exceeds remaining target
        $remaining = $goal->target_amount - $goal->current_amount;
        if ($amount > $remaining && $remaining > 0) {
            Response::error('Allocation amount Rp ' . number_format($amount, 0, ',', '.') . ' exceeds remaining target Rp ' . number_format($remaining, 0, ',', '.') . ' for goal "' . $goal->name . '"', 400);
        }
        
        // Skip if goal already completed
        if ($remaining <= 0) {
            continue;
        }
        
        // Create transaction for this allocation
        $transaction = Transaction::create([
            'goal_id' => $goalId,
            'amount' => $amount,
            'method' => 'allocation',
            'description' => 'Allocation from overflow',
            'transaction_date' => \Carbon\Carbon::now()
        ]);
        
        // Update goal amount
        $result = $goal->addAmount($amount);
        
        // Create notification
        try {
            \App\Models\Notification::createNotification(
                $userId,
                $result['completed'] ? 'Goal Reached!' : 'Allocation Successful',
                'An allocation of Rp ' . number_format($amount, 0, ',', '.') . ' to goal "' . $goal->name . '" was successful.',
                'deposit'
            );
        } catch (\Exception $e) {
            // Ignore notification errors
        }
        
        $allocatedResults[] = [
            'goal_id' => $goalId,
            'goal_name' => $goal->name,
            'amount' => (float) $amount,
            'goal_completed' => $result['completed'],
            'overflow_amount' => (float) $result['overflow_amount']
        ];
        
        $totalAllocated += $amount;
    }
    
    // Handle saving to balance explicitly
    $saveToBalanceAmount = isset($data['save_to_balance_amount']) ? (float)$data['save_to_balance_amount'] : 0;
    
    // Also support legacy save_remaining_as_balance but only if we can determine amount? 
    // Actually, we can't. So we rely on save_to_balance_amount.
    // However, validation allows empty allocations if save_remaining_as_balance is true.
    // We should strictly use save_to_balance_amount if provided.
    
    if ($saveToBalanceAmount > 0) {
        // NO-OP: Money is already in balance from store.php
        // We just acknowledge it.
        // $user->addAvailableBalance($saveToBalanceAmount); // REMOVED to avoid double count
    }
    
    Response::success('Overflow successfully allocated', [
        'allocated' => $allocatedResults,
        'available_balance' => (float) $user->available_balance,
        'saved_to_balance' => (float) $saveToBalanceAmount,
        'total_allocated' => (float) ($totalAllocated + $saveToBalanceAmount)
    ]);
    
} catch (Exception $e) {
    Response::error('Failed to allocate overflow: ' . $e->getMessage(), 500);
}
?>
