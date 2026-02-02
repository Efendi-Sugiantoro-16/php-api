<?php
// cron.php
// Public endpoint for Scheduler (Railway Cron)
// Usage: GET /cron.php?secret=YOUR_CRON_SECRET

require_once __DIR__ . '/bootstrap.php';
use App\Models\Withdrawal;
use App\Helpers\Response;

// 1. Security Check
$secret = getenv('CRON_SECRET') ?: 'default_secret_123'; // Set this in Railway Variables
$inputSecret = $_GET['secret'] ?? '';

if ($inputSecret !== $secret) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// 2. Execute Auto-Approval Logic (Global)
try {
    $count = Withdrawal::processDelayedApprovals(null); // null = check ALL users

    echo json_encode([
        'status' => 'success',
        'processed_count' => $count,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>