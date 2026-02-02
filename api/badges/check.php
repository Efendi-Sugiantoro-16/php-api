<?php
// api/badges/check.php
// Check dan award badges berdasarkan pencapaian user

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Badge;
use App\Models\UserBadge;
use App\Helpers\BadgeHelper;
use App\Helpers\Response;
use App\Middleware\Auth;
use App\Models\Notification;
use Illuminate\Database\Capsule\Manager as Capsule;

$userId = Auth::authenticate();

try {
    $awardedBadges = [];

    // Get user's existing badges
    $existingBadgeIds = UserBadge::where('user_id', $userId)
        ->pluck('badge_id')
        ->toArray();

    // Get all badges
    $allBadges = Badge::all();

    // Calculate user stats using Helper
    $stats = BadgeHelper::calculateUserStats($userId);

    foreach ($allBadges as $badge) {
        // Skip if already owned
        if (in_array($badge->id, $existingBadgeIds)) {
            continue;
        }

        // Check if badge should be awarded
        if (shouldAwardBadge($badge, $stats)) {
            // Award badge
            UserBadge::create([
                'user_id' => $userId,
                'badge_id' => $badge->id,
                'earned_at' => date('Y-m-d H:i:s')
            ]);

            // Create persistent notification
            Notification::createNotification(
                $userId,
                "🎉 Badge Baru: " . $badge->name,
                "Selamat! Kamu mendapatkan badge '" . $badge->name . "' karena " . $badge->description,
                'badge_earned'
            );

            $awardedBadges[] = [
                'id' => $badge->id,
                'code' => $badge->code,
                'name' => $badge->name,
                'description' => $badge->description,
                'icon' => $badge->icon,
            ];
        }
    }

    Response::success('Badge check completed', [
        'new_badges' => $awardedBadges,
        'count' => count($awardedBadges),
        'stats' => $stats
    ]);

} catch (Exception $e) {
    Response::error($e->getMessage(), 500);
}

/**
 * Check if badge should be awarded based on stats
 */
function shouldAwardBadge($badge, $stats)
{
    switch ($badge->requirement_type) {
        case 'first_deposit':
            return $stats['deposit_count'] >= 1;

        case 'streak':
            return $stats['current_streak'] >= $badge->requirement_value
                || $stats['longest_streak'] >= $badge->requirement_value;

        case 'goal_complete':
            return $stats['completed_goals'] >= $badge->requirement_value;

        case 'total_saved':
            return $stats['total_saved'] >= $badge->requirement_value;

        case 'active_goals':
            return $stats['active_goals'] >= $badge->requirement_value;

        case 'deposit_count':
            return $stats['deposit_count'] >= $badge->requirement_value;

        case 'early_complete':
            return $stats['early_complete'] >= $badge->requirement_value;

        default:
            return false;
    }
}
?>