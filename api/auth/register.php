<?php
// api/auth/register.php

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\User;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

$data = Response::getJsonInput();

// Validate input
Response::validateRequiredFields($data, ['name', 'email', 'password']);

$name = trim($data['name']);
$email = trim($data['email']);
$password = $data['password'];

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Response::error('Invalid email format', 400);
}

// Validate password length
if (strlen($password) < 6) {
    Response::error('Password must be at least 6 characters', 400);
}

try {
    // Check if email already exists
    if (User::where('email', $email)->exists()) {
        Response::error('Email already registered', 409);
    }
    
    // Create user
    $user = User::create([
        'name' => $name,
        'email' => $email,
        'password' => $password // Will be hashed automatically by model
    ]);
    
    Response::success('Registration successful', [
        'user_id' => $user->id,
        'name' => $user->name,
        'email' => $user->email
    ], 201);
    
} catch (Exception $e) {
    Response::error('Registration failed: ' . $e->getMessage(), 500);
}
?>
