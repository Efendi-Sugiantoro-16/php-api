<?php
// Migration: Create badges and user_badges tables
// Gamifikasi & Badge System untuk GoalMoney

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

try {
    $pdo = Capsule::connection()->getPdo();

    // Create badges table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS badges (
            id SERIAL PRIMARY KEY,
            code VARCHAR(50) UNIQUE NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            icon VARCHAR(10),
            requirement_type VARCHAR(50),
            requirement_value INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Table 'badges' created successfully\n";

    // Create user_badges table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_badges (
            id SERIAL PRIMARY KEY,
            user_id INT NOT NULL,
            badge_id INT NOT NULL,
            earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE,
            UNIQUE(user_id, badge_id)
        )
    ");
    echo "✅ Table 'user_badges' created successfully\n";

    // Seed default badges
    $badges = [
        ['first_saver', 'First Saver', 'Menabung untuk pertama kali', '🌟', 'first_deposit', 1],
        ['streak_3', 'Getting Started', 'Menabung 3 hari berturut-turut', '⚡', 'streak', 3],
        ['streak_7', 'Week Warrior', 'Menabung 7 hari berturut-turut', '🔥', 'streak', 7],
        ['streak_14', 'Fortnight Fighter', 'Menabung 14 hari berturut-turut', '💪', 'streak', 14],
        ['streak_30', 'Monthly Master', 'Menabung 30 hari berturut-turut', '💎', 'streak', 30],
        ['goal_1', 'Goal Achiever', 'Menyelesaikan goal pertama', '🏆', 'goal_complete', 1],
        ['goal_3', 'Triple Victory', 'Menyelesaikan 3 goal', '🎖️', 'goal_complete', 3],
        ['goal_5', 'Goal Master', 'Menyelesaikan 5 goal', '👑', 'goal_complete', 5],
        ['save_100k', 'Hundred Thousander', 'Total tabungan Rp 100.000', '💵', 'total_saved', 100000],
        ['save_500k', 'Half Millionaire', 'Total tabungan Rp 500.000', '💴', 'total_saved', 500000],
        ['save_1m', 'Millionaire', 'Total tabungan Rp 1.000.000', '💰', 'total_saved', 1000000],
        ['save_5m', 'Multi Millionaire', 'Total tabungan Rp 5.000.000', '💎', 'total_saved', 5000000],
        ['multi_goal_3', 'Multi Tasker', 'Memiliki 3 goal aktif', '🎯', 'active_goals', 3],
        ['deposit_10', 'Regular Saver', 'Melakukan 10 kali deposit', '📊', 'deposit_count', 10],
        ['deposit_50', 'Super Saver', 'Melakukan 50 kali deposit', '📈', 'deposit_count', 50],
        ['early_bird', 'Early Bird', 'Menyelesaikan goal sebelum deadline', '🐦', 'early_complete', 1],
    ];

    $stmt = $pdo->prepare("
        INSERT INTO badges (code, name, description, icon, requirement_type, requirement_value)
        VALUES (?, ?, ?, ?, ?, ?)
        ON CONFLICT (code) DO NOTHING
    ");

    foreach ($badges as $badge) {
        $stmt->execute($badge);
    }
    echo "✅ Seeded " . count($badges) . " default badges\n";

    // Create indexes
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_user_badges_user_id ON user_badges(user_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_user_badges_badge_id ON user_badges(badge_id)");
    echo "✅ Indexes created successfully\n";

    echo "\n🎉 Migration completed successfully!\n";

} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}
