<?php
require_once __DIR__ . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$userId = 5; // Change this to your user ID

echo "=== Checking User $userId Badges ===\n\n";

try {
    $pdo = Capsule::connection()->getPdo();
    
    // Check if badge tables exist
    $tables = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name IN ('badges', 'user_badges')")->fetchAll(PDO::FETCH_COLUMN);
    echo "Badge tables found: " . implode(', ', $tables) . "\n\n";
    
    if (count($tables) < 2) {
        echo "ERROR: Badge tables not found! Please run badge migration first.\n";
        exit;
    }
    
    // Get user badges
    $stmt = $pdo->prepare("
        SELECT 
            b.id,
            b.name,
            b.description,
            b.category,
            b.tier,
            b.icon,
            ub.earned_at
        FROM user_badges ub
        JOIN badges b ON ub.badge_id = b.id
        WHERE ub.user_id = ?
        ORDER BY ub.earned_at DESC
    ");
    $stmt->execute([$userId]);
    $badges = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    echo "Total badges earned: " . count($badges) . "\n\n";
    
    if (count($badges) === 0) {
        echo "User has no badges yet!\n";
        echo "\nTIP: Complete goals or make deposits to earn badges.\n";
    } else {
        echo "User Badges:\n";
        echo str_repeat('-', 80) . "\n";
        foreach ($badges as $badge) {
            echo sprintf(
                "%s %s (%s - %s)\n   %s\n   Earned: %s\n\n",
                $badge->icon,
                $badge->name,
                $badge->tier,
                $badge->category,
                $badge->description,
                $badge->earned_at
            );
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
