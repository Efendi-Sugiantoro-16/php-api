<?php
// index.php

// Simple router for PHP built-in server
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// If request is for root, show documentation
if ($uri === '/' || $uri === '/index.php') {
    require_once __DIR__ . '/bootstrap.php';
    require_once __DIR__ . '/config/cors.php';

    http_response_code(200);

    $response = [
        'success' => true,
        'message' => 'GoalMoney API v1.0',
        'endpoints' => [
            'auth' => [
                'POST /api/auth/register' => 'Register new user',
                'POST /api/auth/login' => 'Login user'
            ],
            'profile' => [
                'GET /api/profile/user' => 'Get user profile (requires auth)'
            ],
            'dashboard' => [
                'GET /api/dashboard/summary' => 'Get dashboard summary (requires auth)'
            ],
            'goals' => [
                'GET /api/goals/index' => 'Get all goals (requires auth)',
                'POST /api/goals/store' => 'Create new goal (requires auth)',
                'PUT /api/goals/update' => 'Update goal (requires auth)',
                'DELETE /api/goals/delete' => 'Delete goal (requires auth)'
            ],
            'transactions' => [
                'GET /api/transactions/index?goal_id={id}' => 'Get transactions by goal (requires auth)',
                'POST /api/transactions/store' => 'Create new transaction (requires auth)',
                'DELETE /api/transactions/delete' => 'Delete transaction (requires auth)'
            ]
        ],
        'authentication' => [
            'type' => 'Bearer Token',
            'header' => 'Authorization: Bearer {token}'
        ]
    ];

    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

// Check if file exists based on URI
$filePath = __DIR__ . $uri . '.php';

if (file_exists($filePath)) {
    // Determine if we need to load bootstrap first? 
    // The individual files already load bootstrap via relative path, so simply require matching file.
    require $filePath;
} else {
    // 404 Not Found
    require_once __DIR__ . '/bootstrap.php';
    require_once __DIR__ . '/config/cors.php';
    
    // Check if it's an options request (CORS) - handled in cors.php but we need to trigger it
    // Actually config/cors.php handles EXIT if OPTIONS.
    
    // Return JSON 404
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
}
?>
