<?php
// api/withdrawals/request.php
// POST /api/withdrawals/request - User minta penarikan

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

// Validate input
Response::validateRequiredFields($data, ['amount', 'method']);

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
    // Calculate user's total balance from all goals
    $totalBalance = Goal::where('user_id', $userId)->sum('current_amount');
    
    // Validate sufficient balance
    if ($totalBalance < $amount) {
        Response::error('Insufficient balance. Your total savings: Rp ' . number_format($totalBalance, 2), 400);
    }
    
    // Create withdrawal request
    $withdrawal = Withdrawal::create([
        'user_id' => $userId,
        'amount' => $amount,
        'method' => $method,
        'account_number' => $accountNumber,
        'status' => Withdrawal::STATUS_PENDING,
        'notes' => $notes
    ]);
    
    Response::success('Withdrawal request submitted successfully', [
        'id' => $withdrawal->id,
        'amount' => (float) $withdrawal->amount,
        'method' => $withdrawal->method,
        'account_number' => $withdrawal->account_number,
        'status' => $withdrawal->status,
        'notes' => $withdrawal->notes,
        'total_balance' => (float) $totalBalance,
        'created_at' => $withdrawal->created_at->toDateTimeString()
    ], 201);
    
} catch (Exception $e) {
    Response::error('Failed to create withdrawal request: ' . $e->getMessage(), 500);
}
?>
