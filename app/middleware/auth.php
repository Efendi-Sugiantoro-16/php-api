<?php
// app/middleware/auth.php

namespace App\Middleware;

use App\Models\Token;
use App\Helpers\Response;

class Auth {
    public static function authenticate() {
        $headers = getallheaders();
        
        if (!isset($headers['Authorization'])) {
            Response::error('Authorization header missing', 401);
        }
        
        $authHeader = $headers['Authorization'];
        
        // Expected format: Bearer <token>
        if (strpos($authHeader, 'Bearer ') !== 0) {
            Response::error('Invalid authorization format', 401);
        }
        
        $tokenString = substr($authHeader, 7);
        
        if (empty($tokenString)) {
            Response::error('Token is empty', 401);
        }
        
        $userId = Token::verify($tokenString);
        
        if (!$userId) {
            Response::error('Invalid or expired token', 401);
        }
        
        return $userId;
    }
}
?>
