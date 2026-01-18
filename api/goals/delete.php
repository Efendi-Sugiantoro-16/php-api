<?php
// api/goals/delete.php

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Goal;
use App\Middleware\Auth;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    Response::error('Method not allowed', 405);
}

$userId = Auth::authenticate();
$data = Response::getJsonInput();

// Validate input
Response::validateRequiredFields($data, ['id']);

$goalId = (int) $data['id'];

try {
    // Find goal and check ownership
    $goal = Goal::where('id', $goalId)
                ->where('user_id', $userId)
                ->first();
    
    if (!$goal) {
        Response::error('Goal not found or access denied', 404);
    }
    
    // Delete goal (cascade will delete transactions via database)
    $goal->delete();
    
    Response::success('Goal deleted successfully');
    
} catch (Exception $e) {
    Response::error('Failed to delete goal: ' . $e->getMessage(), 500);
}
?>
