<?php
// test_analytics_api.php - Test analytics API dengan token dari database

require_once __DIR__ . '/bootstrap.php';

use App\Models\Token;
use App\Models\Goal;
use App\Models\Transaction;
use App\Models\User;

header('Content-Type: application/json');

// Get latest valid token
$latestToken = Token::orderBy('id', 'desc')->first();

if (!$latestToken) {
    echo json_encode(['error' => 'No tokens found']);
    exit;
}

$userId = $latestToken->user_id;
$tokenString = $latestToken->token;

// Check data for this user
$goals = Goal::where('user_id', $userId)->get();
$totalSaved = Goal::where('user_id', $userId)->sum('current_amount');
$activeGoals = Goal::where('user_id', $userId)
    ->whereRaw('current_amount < target_amount')
    ->count();
$completedGoals = Goal::where('user_id', $userId)
    ->whereRaw('current_amount >= target_amount')
    ->count();

$transactionCount = Transaction::whereHas('goal', function($q) use ($userId) {
    $q->where('user_id', $userId);
})->count();

// Get all users and their data for comparison
$allUsersData = [];
foreach (User::all() as $user) {
    $allUsersData[] = [
        'user_id' => $user->id,
        'name' => $user->name,
        'goals' => Goal::where('user_id', $user->id)->count(),
        'total_saved' => (float) Goal::where('user_id', $user->id)->sum('current_amount'),
    ];
}

echo json_encode([
    'token_info' => [
        'token_id' => $latestToken->id,
        'user_id_from_token' => $userId,
        'token_preview' => substr($tokenString, 0, 20) . '...',
    ],
    'user_data' => [
        'user_id' => $userId,
        'goals_count' => $goals->count(),
        'total_saved' => (float) $totalSaved,
        'active_goals' => $activeGoals,
        'completed_goals' => $completedGoals,
        'transaction_count' => $transactionCount,
    ],
    'all_users_comparison' => $allUsersData,
    'goals_detail' => $goals->map(fn($g) => [
        'id' => $g->id,
        'name' => $g->name,
        'user_id' => $g->user_id,
        'current_amount' => (float) $g->current_amount,
    ])->toArray(),
], JSON_PRETTY_PRINT);

?>
