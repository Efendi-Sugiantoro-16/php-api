<?php
// debug_analytics.php - Debug untuk cek analytics data

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/config/cors.php';

use App\Models\Goal;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Token;

echo "<h2>Debug Analytics Data</h2>";

// Cari token dari database
$tokens = Token::orderBy('id', 'desc')->take(5)->get();
echo "<h3>Latest Tokens:</h3>";
echo "<pre>";
foreach ($tokens as $token) {
    echo "Token ID: {$token->id}, User ID: {$token->user_id}, Token: " . substr($token->token, 0, 20) . "...\n";
}
echo "</pre>";

// Cari user dengan goals
$users = User::all();
echo "<h3>Users and their data:</h3>";
echo "<pre>";
foreach ($users as $user) {
    $goalCount = Goal::where('user_id', $user->id)->count();
    $totalSaved = Goal::where('user_id', $user->id)->sum('current_amount');
    $transactionCount = Transaction::whereHas('goal', function($q) use ($user) {
        $q->where('user_id', $user->id);
    })->count();
    
    echo "User ID: {$user->id}, Name: {$user->name}\n";
    echo "  Goals: $goalCount, Total Saved: Rp " . number_format($totalSaved, 0, ',', '.') . "\n";
    echo "  Transactions: $transactionCount\n";
    echo "---\n";
}
echo "</pre>";

// Cek detail goals
echo "<h3>All Goals:</h3>";
echo "<pre>";
$goals = Goal::all();
foreach ($goals as $goal) {
    echo "Goal ID: {$goal->id}, User ID: {$goal->user_id}, Name: {$goal->name}\n";
    echo "  Target: Rp " . number_format($goal->target_amount, 0, ',', '.') . "\n";
    echo "  Current: Rp " . number_format($goal->current_amount, 0, ',', '.') . "\n";
    echo "---\n";
}
echo "</pre>";

// Cek transactions
echo "<h3>All Transactions:</h3>";
echo "<pre>";
$transactions = Transaction::orderBy('id', 'desc')->take(10)->get();
foreach ($transactions as $tx) {
    echo "TX ID: {$tx->id}, Goal ID: {$tx->goal_id}, Amount: Rp " . number_format($tx->amount, 0, ',', '.') . "\n";
    echo "  Date: {$tx->transaction_date}\n";
    echo "---\n";
}
echo "</pre>";

?>
