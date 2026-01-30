<?php
// test_create_goal.php
require_once __DIR__ . '/bootstrap.php';
use App\Models\User;
use App\Models\Token;

try {
    // Get first user
    $user = User::first();
    if (!$user) {
        die("No user found. Please register first.\n");
    }
    
    // Generate token
    $token = Token::generateToken($user->id);
    $tokenString = $token->token;
    
    echo "Using User ID: " . $user->id . "\n";
    echo "Token: " . $tokenString . "\n";
    
    // Simulate POST request to api/goals/store.php
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $tokenString;
    
    // Mock getallheaders for built-in server/CLI
    if (!function_exists('getallheaders')) {
        function getallheaders() {
            return ['Authorization' => $_SERVER['HTTP_AUTHORIZATION']];
        }
    }
    
    // Mock input
    $data = [
        'name' => 'Test Goal ' . time(),
        'target_amount' => 1000000,
        'type' => 'digital',
        'description' => 'Test description'
    ];
    
    // We can't easily mock php://input for Response::getJsonInput() in the same process
    // but we can modify Response::getJsonInput() temporarily or bypass it.
    // Instead, let's just run it via curl if the server is running.
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
