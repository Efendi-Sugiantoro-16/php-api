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

if (empty($allocations)) {
    Response::error('Allocations cannot be empty', 400);
}

try {
    $user = User::find($userId);
    if (!$user) {
        Response::error('User not found', 404);
    }
    
    $allocatedResults = [];
    $totalAllocated = 0;
    
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
            'description' => 'Alokasi dari overflow',
            'transaction_date' => \Carbon\Carbon::now()
        ]);
        
        // Update goal amount
        $result = $goal->addAmount($amount);
        
        // Create notification
        try {
            \App\Models\Notification::createNotification(
                $userId,
                $result['completed'] ? 'Goal Tercapai!' : 'Alokasi Berhasil',
                'Alokasi sebesar Rp ' . number_format($amount, 0, ',', '.') . ' ke goal "' . $goal->name . '" berhasil.',
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
    
    // Calculate remaining after allocations
    $remainingBalance = 0;
    if ($saveRemainingAsBalance) {
        // This would be calculated from the original overflow amount
        // For now, we'll assume the remaining is passed or calculated elsewhere
        // In real implementation, you might want to track the original overflow amount
    }
    
    Response::success('Overflow successfully allocated', [
        'allocated' => $allocatedResults,
        'available_balance' => (float) $user->available_balance,
        'total_allocated' => (float) $totalAllocated
    ]);
    
} catch (Exception $e) {
    Response::error('Failed to allocate overflow: ' . $e->getMessage(), 500);
}
?>
