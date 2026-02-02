<?php
// api/notifications/index.php
// GET /api/notifications/index - List user notifications

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Notification;
use App\Middleware\Auth;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

$userId = Auth::authenticate();

try {
    // Get notifications ordered by created_at desc
    $notifications = Notification::where('user_id', $userId)
        ->orderBy('created_at', 'desc')
        ->take(50) // Limit to last 50
        ->get();

    // Format response
    $formattedNotifications = [];
    foreach ($notifications as $notif) {
        $formattedNotifications[] = [
            'id' => $notif->id,
            'title' => $notif->title,
            'message' => $notif->message,
            'type' => $notif->type,
            'is_read' => (bool) $notif->is_read,
            'created_at' => $notif->created_at ? $notif->created_at->format('Y-m-d H:i:s') : null
        ];
    }

    Response::success('Notifications retrieved successfully', $formattedNotifications);

} catch (Exception $e) {
    Response::error('Failed to retrieve notifications: ' . $e->getMessage(), 500);
}
?>