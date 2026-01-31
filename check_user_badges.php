<?php
require_once __DIR__ . '/bootstrap.php';
use Illuminate\Database\Capsule\Manager as Capsule;

$userId = 5;
$pdo = Capsule::connection()->getPdo();

$stmt = $pdo->prepare("
    SELECT 
        b.code,
        b.name,
        b.icon,
        ub.earned_at
    FROM user_badges ub
    JOIN badges b ON ub.badge_id = b.id
    WHERE ub.user_id = ?
    ORDER BY ub.earned_at DESC
");
$stmt->execute([$userId]);
$badges = $stmt->fetchAll(PDO::FETCH_OBJ);

echo "User $userId badges: " . count($badges) . " earned\n\n";

if (count($badges) > 0) {
    foreach ($badges as $badge) {
        echo "{$badge->icon} {$badge->name} - {$badge->code}\n";
        echo "   Earned: {$badge->earned_at}\n\n";
    }
} else {
    echo "No badges earned yet. Start completing goals to earn badges!\n";
}
?>
