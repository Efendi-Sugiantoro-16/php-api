<?php
// api/badges/check.php
// Check dan award badges berdasarkan pencapaian user

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Badge;
use App\Models\UserBadge;
use App\Models\Goal;
use App\Models\Transaction;
use App\Helpers\Response;
use App\Middleware\Auth;
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
    
    // Calculate user stats
    $stats = calculateUserStats($userId);
    
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
 * Calculate user statistics for badge checking
 */
function calculateUserStats($userId) {
    // Total deposits count
    $depositCount = Transaction::whereHas('goal', function($q) use ($userId) {
        $q->where('user_id', $userId);
    })->count();
    
    // Total saved amount
    $totalSaved = Goal::where('user_id', $userId)->sum('current_amount');
    
    // Completed goals count
    $completedGoals = Goal::where('user_id', $userId)
        ->whereRaw('current_amount >= target_amount')
        ->count();
    
    // Active goals count
    $activeGoals = Goal::where('user_id', $userId)
        ->whereRaw('current_amount < target_amount')
        ->count();
    
    // Calculate streak
    $streak = calculateStreak($userId);
    
    // Early complete count (goals completed before deadline)
    $earlyComplete = Goal::where('user_id', $userId)
        ->whereRaw('current_amount >= target_amount')
        ->whereRaw('deadline IS NOT NULL')
        ->whereRaw('updated_at < deadline')
        ->count();
    
    return [
        'deposit_count' => $depositCount,
        'total_saved' => (float) $totalSaved,
        'completed_goals' => $completedGoals,
        'active_goals' => $activeGoals,
        'current_streak' => $streak['current'],
        'longest_streak' => $streak['longest'],
        'early_complete' => $earlyComplete
    ];
}

/**
 * Calculate deposit streak
 */
function calculateStreak($userId) {
    // Get all deposit dates ordered by date
    $deposits = Transaction::whereHas('goal', function($q) use ($userId) {
        $q->where('user_id', $userId);
    })
    ->selectRaw('DATE(transaction_date) as deposit_date')
    ->groupBy('deposit_date')
    ->orderBy('deposit_date', 'desc')
    ->pluck('deposit_date')
    ->toArray();
    
    if (empty($deposits)) {
        return ['current' => 0, 'longest' => 0];
    }
    
    $currentStreak = 0;
    $longestStreak = 0;
    $tempStreak = 1;
    
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    // Check current streak (must include today or yesterday)
    if ($deposits[0] == $today || $deposits[0] == $yesterday) {
        $currentStreak = 1;
        for ($i = 1; $i < count($deposits); $i++) {
            $prevDate = date('Y-m-d', strtotime($deposits[$i-1] . ' -1 day'));
            if ($deposits[$i] == $prevDate) {
                $currentStreak++;
            } else {
                break;
            }
        }
    }
    
    // Calculate longest streak
    for ($i = 1; $i < count($deposits); $i++) {
        $prevDate = date('Y-m-d', strtotime($deposits[$i-1] . ' -1 day'));
        if ($deposits[$i] == $prevDate) {
            $tempStreak++;
        } else {
            $longestStreak = max($longestStreak, $tempStreak);
            $tempStreak = 1;
        }
    }
    $longestStreak = max($longestStreak, $tempStreak, $currentStreak);
    
    return [
        'current' => $currentStreak,
        'longest' => $longestStreak
    ];
}

/**
 * Check if badge should be awarded based on stats
 */
function shouldAwardBadge($badge, $stats) {
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
