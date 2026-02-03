<?php
// api/profile/update.php

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
Response::validateRequiredFields($data, ['name', 'email']);

$name = trim($data['name']);
$email = trim($data['email']);

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Response::error('Invalid email format', 400);
}

try {
    $user = User::find($userId);

    if (!$user) {
        Response::error('User not found', 404);
    }

    // Check if email is already taken by another user
    if ($email !== $user->email) {
        if (User::where('email', $email)->where('id', '!=', $userId)->exists()) {
            Response::error('Email already in use by another account', 409);
        }
    }

    // Update user info
    $user->name = $name;
    $user->email = $email;
    $user->save();

    Response::success('Profile updated successfully', [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email
    ]);

} catch (Exception $e) {
    Response::error('Failed to update profile: ' . $e->getMessage(), 500);
}
?>