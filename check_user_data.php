<?php
// check_user_data.php
require_once __DIR__ . '/bootstrap.php';
use App\Models\User;
use App\Models\Goal;
use App\Models\Token;

header('Content-Type: text/plain');

echo "=== USER DATA CHECK ===\n\n";

$users = User::all();
foreach ($users as $u) {
    $gc = Goal::where('user_id', $u->id)->count();
    $ts = Goal::where('user_id', $u->id)->sum('current_amount');
    echo "User {$u->id} ({$u->name}): {$gc} goals, Rp {$ts}\n";
}

echo "\n=== LATEST TOKENS ===\n\n";
$tokens = Token::orderBy('id', 'desc')->take(5)->get();
foreach ($tokens as $t) {
    echo "Token ID {$t->id} -> User ID {$t->user_id}\n";
}

echo "\n=== GOALS BY USER ===\n\n";
$goals = Goal::all();
foreach ($goals as $g) {
    echo "Goal #{$g->id} (user_id={$g->user_id}): {$g->name} - Rp {$g->current_amount}/{$g->target_amount}\n";
}
