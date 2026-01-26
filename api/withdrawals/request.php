<?php
// api/withdrawals/request.php
// POST /api/withdrawals/request - User request penarikan dari goal spesifik

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Goal;
use App\Models\Withdrawal;
use App\Middleware\Auth;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

$userId = Auth::authenticate();
$data = Response::getJsonInput();

// Validate input - goal_id is optional now
Response::validateRequiredFields($data, ['amount', 'method']);

$goalId = isset($data['goal_id']) ? (int) $data['goal_id'] : null;
$amount = (float) $data['amount'];
$method = strtolower(trim($data['method']));
$accountNumber = isset($data['account_number']) ? trim($data['account_number']) : null;
$notes = isset($data['notes']) ? trim($data['notes']) : null;

// Validate amount
if ($amount <= 0) {
    Response::error('Amount must be greater than 0', 400);
}

// Validate method
if (!Withdrawal::isValidMethod($method)) {
    Response::error('Invalid method. Valid methods are: ' . implode(', ', Withdrawal::VALID_METHODS), 400);
}

try {
    // Validate source of funds
    if ($goalId) {
        // Validate goal exists and belongs to user
        $goal = Goal::where('id', $goalId)
                    ->where('user_id', $userId)
                    ->first();
        
        if (!$goal) {
            Response::error('Goal not found or does not belong to you', 404);
        }
        
        // Validate sufficient balance in THIS SPECIFIC GOAL
        if ($goal->current_amount < $amount) {
            Response::error(
                'Insufficient balance in "' . $goal->name . '". Available: Rp ' . number_format($goal->current_amount, 0, ',', '.'),
                400
            );
        }
    } else {
        // Withdraw from AVAILABLE BALANCE
        $user = \App\Models\User::find($userId);
        if ($user->available_balance < $amount) {
            Response::error(
                'Insufficient available balance. Available: Rp ' . number_format($user->available_balance, 0, ',', '.'),
                400
            );
        }
        // Deduct from available balance immediately or upon approval?
        // Usually upon request to prevent double spending.
        $user->subtractAvailableBalance($amount);
    }
    
    // Create withdrawal request with goal_id
    $withdrawal = Withdrawal::create([
        'user_id' => $userId,
        'goal_id' => $goalId,
        'amount' => $amount,
        'method' => $method,
        'account_number' => $accountNumber,
        'status' => Withdrawal::STATUS_PENDING,
        'notes' => $notes
    ]);
    
    Response::success('Withdrawal request submitted successfully', [
        'id' => $withdrawal->id,
        'goal_id' => $withdrawal->goal_id,
        'goal_name' => $goalId ? $goal->name : 'Available Balance',
        'amount' => (float) $withdrawal->amount,
        'method' => $withdrawal->method,
        'account_number' => $withdrawal->account_number,
        'status' => $withdrawal->status,
        'notes' => $withdrawal->notes,
        'goal_balance' => $goalId ? (float) $goal->current_amount : (float) $user->available_balance,
        'created_at' => $withdrawal->created_at->toDateTimeString()
    ], 201);
    
} catch (Exception $e) {
    Response::error('Failed to create withdrawal request: ' . $e->getMessage(), 500);
}
?>
