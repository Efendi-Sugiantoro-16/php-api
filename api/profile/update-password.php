<?php
// api/profile/update-password.php

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\User;
use App\Middleware\Auth;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

// Authenticate user
$userId = Auth::authenticate();

$data = Response::getJsonInput();

// Validate input
Response::validateRequiredFields($data, ['old_password', 'new_password']);

$oldPassword = $data['old_password'];
$newPassword = $data['new_password'];

// Validate new password length
if (strlen($newPassword) < 6) {
    Response::error('New password must be at least 6 characters', 400);
}

try {
    $user = User::find($userId);

    if (!$user) {
        Response::error('User not found', 404);
    }

    // Verify old password
    if (!$user->verifyPassword($oldPassword)) {
        Response::error('Current password is incorrect', 401);
    }

    // Update password
    $user->password = $newPassword; // Automatically hashed by Model's setPasswordAttribute
    $user->save();

    Response::success('Password updated successfully');

} catch (Exception $e) {
    Response::error('Failed to update password: ' . $e->getMessage(), 500);
}
?>