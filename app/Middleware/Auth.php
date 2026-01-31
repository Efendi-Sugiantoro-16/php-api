<?php


namespace App\Middleware;

use App\Models\Token;
use App\Helpers\Response;

class Auth {
    public static function authenticate() {
        $headers = getallheaders();
        $authHeader = null;

        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $authHeader = $headers['authorization'];
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        
        if (!$authHeader) {
            Response::error('Authorization header missing', 401);
        }
        
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
