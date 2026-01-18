<?php
// api/withdrawals/index.php
// GET /api/withdrawals/index - Lihat history withdrawal

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Withdrawal;
use App\Middleware\Auth;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

$userId = Auth::authenticate();

try {
    // Trigger auto-approval check
    Withdrawal::processDelayedApprovals($userId);

    // Build query
    $query = Withdrawal::where('user_id', $userId);
    
    // Filter by status if provided
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $status = strtolower(trim($_GET['status']));
        $validStatuses = ['pending', 'approved', 'rejected'];
        
        if (in_array($status, $validStatuses)) {
            $query->where('status', $status);
        }
    }
    
    // Get withdrawals ordered by created_at desc
    $withdrawals = $query->orderBy('created_at', 'desc')->get();
    
    // Format response
    $formattedWithdrawals = [];
    foreach ($withdrawals as $withdrawal) {
        $formattedWithdrawals[] = [
            'id' => $withdrawal->id,
            'amount' => (float) $withdrawal->amount,
            'method' => $withdrawal->method,
            'account_number' => $withdrawal->account_number,
            'status' => $withdrawal->status,
            'notes' => $withdrawal->notes,
            'created_at' => $withdrawal->created_at->toDateTimeString(),
            'updated_at' => $withdrawal->updated_at ? $withdrawal->updated_at->toDateTimeString() : null
        ];
    }
    
    // Calculate summary
    $summary = [
        'total_requests' => count($formattedWithdrawals),
        'pending_count' => Withdrawal::where('user_id', $userId)->where('status', 'pending')->count(),
        'approved_count' => Withdrawal::where('user_id', $userId)->where('status', 'approved')->count(),
        'rejected_count' => Withdrawal::where('user_id', $userId)->where('status', 'rejected')->count()
    ];
    
    Response::success('Withdrawals retrieved successfully', [
        'summary' => $summary,
        'withdrawals' => $formattedWithdrawals
    ]);
    
} catch (Exception $e) {
    Response::error('Failed to retrieve withdrawals: ' . $e->getMessage(), 500);
}
?>
