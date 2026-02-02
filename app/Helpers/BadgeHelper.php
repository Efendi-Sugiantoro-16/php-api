<?php
// app/Helpers/BadgeHelper.php

namespace App\Helpers;

use App\Models\Badge;
use App\Models\UserBadge;
use App\Models\Goal;
use App\Models\Transaction;

class BadgeHelper
{
    /**
     * Calculate user statistics for badge checking
     */
    public static function calculateUserStats($userId)
    {
        // Total deposits count
        $depositCount = Transaction::whereHas('goal', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->count();

        // Total historical saved amount (Sum of all deposits)
        $totalSaved = Transaction::whereHas('goal', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->sum('amount');

        // Completed goals count
        $completedGoals = Goal::where('user_id', $userId)
            ->whereRaw('current_amount >= target_amount')
            ->count();

        // Active goals count
        $activeGoals = Goal::where('user_id', $userId)
            ->whereRaw('current_amount < target_amount')
            ->count();

        // Calculate streak
        $streak = self::calculateStreak($userId);

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
    public static function calculateStreak($userId)
    {
        // Get all deposit dates ordered by date
        $deposits = Transaction::whereHas('goal', function ($q) use ($userId) {
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
                $prevDate = date('Y-m-d', strtotime($deposits[$i - 1] . ' -1 day'));
                if ($deposits[$i] == $prevDate) {
                    $currentStreak++;
                } else {
                    break;
                }
            }
        }

        // Calculate longest streak
        for ($i = 1; $i < count($deposits); $i++) {
            $prevDate = date('Y-m-d', strtotime($deposits[$i - 1] . ' -1 day'));
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
     * Get current value for a specific requirement type from stats
     */
    public static function getCurrentValueForRequirement($type, $stats)
    {
        switch ($type) {
            case 'first_deposit':
                return $stats['deposit_count'] > 0 ? 1 : 0;
            case 'streak':
                return max($stats['current_streak'], $stats['longest_streak']);
            case 'goal_complete':
                return $stats['completed_goals'];
            case 'total_saved':
                return $stats['total_saved'];
            case 'active_goals':
                return $stats['active_goals'];
            case 'deposit_count':
                return $stats['deposit_count'];
            case 'early_complete':
                return $stats['early_complete'];
            default:
                return 0;
        }
    }
}
