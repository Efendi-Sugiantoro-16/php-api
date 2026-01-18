<?php
// api/withdrawals/approve.php
// POST /api/withdrawals/approve - Admin approve/reject withdrawal (Dummy - no role check)

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Goal;
use App\Models\Withdrawal;
use App\Middleware\Auth;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

// Note: In production, add admin role check here
$userId = Auth::authenticate();
$data = Response::getJsonInput();

// Validate input
Response::validateRequiredFields($data, ['withdrawal_id']);

$withdrawalId = (int) $data['withdrawal_id'];
$action = isset($data['action']) ? strtolower(trim($data['action'])) : 'approve';
$notes = isset($data['notes']) ? trim($data['notes']) : null;

// Validate action
if (!in_array($action, ['approve', 'reject'])) {
    Response::error('Invalid action. Valid actions are: approve, reject', 400);
}

try {
    // Find withdrawal
    $withdrawal = Withdrawal::find($withdrawalId);
    
    if (!$withdrawal) {
        Response::error('Withdrawal not found', 404);
    }
    
    // Check if already processed
    if (!$withdrawal->isPending()) {
        Response::error('Withdrawal has already been processed. Current status: ' . $withdrawal->status, 400);
    }
    
    if ($action === 'approve') {
        // Check if user has sufficient balance
        $totalBalance = Goal::where('user_id', $withdrawal->user_id)->sum('current_amount');
        
        if ($totalBalance < $withdrawal->amount) {
            Response::error('User has insufficient balance for this withdrawal', 400);
        }
        
        // Deduct from user's goals (proportionally from each goal)
        $amountToDeduct = $withdrawal->amount;
        $goals = Goal::where('user_id', $withdrawal->user_id)
                     ->where('current_amount', '>', 0)
                     ->orderBy('current_amount', 'desc')
                     ->get();
        
        foreach ($goals as $goal) {
            if ($amountToDeduct <= 0) break;
            
            $deductFromThisGoal = min($goal->current_amount, $amountToDeduct);
            $goal->subtractAmount($deductFromThisGoal);
            $amountToDeduct -= $deductFromThisGoal;
        }
        
        // Approve withdrawal
        $withdrawal->approve($notes ?? 'Withdrawal approved');
        
        // Notification
        \App\Models\Notification::createNotification(
            $userId,
            'Penarikan Disetujui',
            'Penarikan dana sebesar Rp ' . number_format($withdrawal->amount, 0, ',', '.') . ' telah disetujui.',
            'withdrawal'
        );
        
        Response::success('Withdrawal approved successfully', [
            'id' => $withdrawal->id,
            'amount' => (float) $withdrawal->amount,
            'method' => $withdrawal->method,
            'status' => $withdrawal->status,
            'notes' => $withdrawal->notes,
            'updated_at' => $withdrawal->updated_at->toDateTimeString()
        ]);
        
    } else {
        // Reject withdrawal
        $withdrawal->reject($notes ?? 'Withdrawal rejected');
        
        // Notification
        \App\Models\Notification::createNotification(
            $userId,
            'Penarikan Ditolak',
            'Penarikan dana sebesar Rp ' . number_format($withdrawal->amount, 0, ',', '.') . ' ditolak. Catatan: ' . ($notes ?? '-'),
            'withdrawal'
        );
        
        Response::success('Withdrawal rejected', [
            'id' => $withdrawal->id,
            'amount' => (float) $withdrawal->amount,
            'status' => $withdrawal->status,
            'notes' => $withdrawal->notes,
            'updated_at' => $withdrawal->updated_at->toDateTimeString()
        ]);
    }
    
} catch (Exception $e) {
    Response::error('Failed to process withdrawal: ' . $e->getMessage(), 500);
}
?>
