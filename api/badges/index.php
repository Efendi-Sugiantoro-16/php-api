<?php
// api/badges/index.php
// Get all badges dan badges milik user

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Badge;
use App\Models\UserBadge;
use App\Helpers\BadgeHelper;
use App\Helpers\Response;
use App\Middleware\Auth;

// Authenticate user
$userId = Auth::authenticate();

try {
    // Get all available badges
    $allBadges = Badge::orderBy('id')->get();
    
    // Get user statistics
    $stats = BadgeHelper::calculateUserStats($userId);
    
    // Get user's earned badges
    $userBadges = UserBadge::where('user_id', $userId)
        ->get()
        ->keyBy('badge_id');
    
    // Format response with progress tracking
    $badges = $allBadges->map(function($badge) use ($userBadges, $stats) {
        $earned = isset($userBadges[$badge->id]);
        return [
            'id' => $badge->id,
            'code' => $badge->code,
            'name' => $badge->name,
            'description' => $badge->description,
            'icon' => $badge->icon,
            'requirement_type' => $badge->requirement_type,
            'requirement_value' => (int) $badge->requirement_value,
            'current_value' => BadgeHelper::getCurrentValueForRequirement($badge->requirement_type, $stats),
            'earned' => $earned,
            'earned_at' => $earned ? $userBadges[$badge->id]->earned_at : null,
        ];
    });
    
    // Count stats
    $earnedCount = $userBadges->count();
    $totalCount = $allBadges->count();
    $progress = $totalCount > 0 ? round(($earnedCount / $totalCount) * 100, 1) : 0;
    
    Response::success('Badges retrieved', [
        'badges' => $badges,
        'stats' => array_merge($stats, [
            'earned' => $earnedCount,
            'total' => $totalCount,
            'progress' => $progress
        ])
    ]);
    
} catch (Exception $e) {
    Response::error($e->getMessage(), 500);
}
