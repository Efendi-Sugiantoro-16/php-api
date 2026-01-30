<?php
// api/auth/login.php

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\User;
use App\Models\Token;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

$data = Response::getJsonInput();

// Validate input
Response::validateRequiredFields($data, ['email', 'password']);

$email = trim($data['email']);
$password = $data['password'];

try {
    // Get user by email
    $user = User::where('email', $email)->first();
    
    if (!$user) {
        Response::error('Invalid email or password', 401);
    }
    
    // Check if password hash exists
    if (empty($user->password)) {
        Response::error('Account has no password set. Please contact support.', 403);
    }

    // Verify password
    if (!password_verify($password, $user->password)) {
        Response::error('Invalid email or password', 401);
    }
    
    // Generate token
    $token = Token::generateToken($user->id);
    
    Response::success('Login successful', [
        'token' => $token->token,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'available_balance' => isset($user->available_balance) ? (float)$user->available_balance : 0
        ]
    ]);
    
} catch (Exception $e) {
    Response::error('Login failed: ' . $e->getMessage(), 500);
}
?>
