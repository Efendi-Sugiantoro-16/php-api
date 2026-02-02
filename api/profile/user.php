<?php
// api/profile/user.php

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\User;
use App\Middleware\Auth;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

$userId = Auth::authenticate();

try {
    $user = User::find($userId);

    if (!$user) {
        Response::error('User not found', 404);
    }

    Response::success('User profile retrieved successfully', [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'created_at' => $user->created_at->toDateTimeString()
    ]);

} catch (Exception $e) {
    Response::error('Failed to retrieve user profile: ' . $e->getMessage(), 500);
}
?>