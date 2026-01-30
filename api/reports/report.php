<?php
// api/reports/report.php
// Endpoint untuk generate Laporan Progress Tabungan GoalMoney

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Goal;
use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Middleware\Auth;
use App\Helpers\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

$userId = Auth::authenticate();

try {
    // Get optional date filters
    $startDate = $_GET['start_date'] ?? date('Y-m-01'); // Default: awal bulan ini
    $endDate = $_GET['end_date'] ?? date('Y-m-d'); // Default: hari ini
    
    // Get optional search filter
    $search = $_GET['search'] ?? null;
    
    // Get all goals for this user
    $query = Goal::where('user_id', $userId);
    
    // Apply search filter if provided
    if ($search) {
        $query->where(function($q) use ($search) {
            $q->where('name', 'like', '%' . $search . '%')
              ->orWhere('description', 'like', '%' . $search . '%');
        });
    }
    
    $goals = $query->get();
    
    // ===== SUMMARY STATISTICS =====
    $totalGoals = $goals->count();
    $totalSaved = $goals->sum('current_amount');
    $totalTarget = $goals->sum('target_amount');
    
    $completedGoals = $goals->filter(function($goal) {
        return $goal->current_amount >= $goal->target_amount;
    });
    $completedCount = $completedGoals->count();
    $activeCount = $totalGoals - $completedCount;
    
    $overallProgress = $totalTarget > 0 ? round(($totalSaved / $totalTarget) * 100, 2) : 0;
    
    // Count total deposits in period
    $totalDeposits = Transaction::whereHas('goal', function($q) use ($userId) {
            $q->where('user_id', $userId);
        })
        ->whereBetween('transaction_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
        ->count();
    
    // Sum deposits in period
    $periodSavings = Transaction::whereHas('goal', function($q) use ($userId) {
            $q->where('user_id', $userId);
        })
        ->whereBetween('transaction_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
        ->sum('amount');
    
    // Count withdrawals
    $totalWithdrawals = Withdrawal::where('user_id', $userId)
        ->where('status', 'approved')
        ->count();
    
    // ===== GOAL DETAILS =====
    $goalDetails = [];
    foreach ($goals as $goal) {
        $depositCount = Transaction::where('goal_id', $goal->id)->count();
        $lastDeposit = Transaction::where('goal_id', $goal->id)
            ->orderBy('transaction_date', 'desc')
            ->first();
        
        $status = $goal->current_amount >= $goal->target_amount ? 'completed' : 'active';
        $remaining = max(0, $goal->target_amount - $goal->current_amount);
        
        // Calculate days remaining to deadline
        $daysRemaining = null;
        if ($goal->deadline && $status === 'active') {
            $deadline = new DateTime($goal->deadline);
            $today = new DateTime();
            $diff = $today->diff($deadline);
            $daysRemaining = $diff->invert ? -$diff->days : $diff->days;
        }
        
        $goalDetails[] = [
            'id' => $goal->id,
            'name' => $goal->name,
            'type' => $goal->type ?? 'digital',
            'target_amount' => (float) $goal->target_amount,
            'current_amount' => (float) $goal->current_amount,
            'remaining_amount' => (float) $remaining,
            'progress_percentage' => $goal->progress_percentage,
            'status' => $status,
            'deadline' => $goal->deadline ? $goal->deadline->format('Y-m-d') : null,
            'days_remaining' => $daysRemaining,
            'total_deposits' => $depositCount,
            'last_deposit_date' => $lastDeposit ? $lastDeposit->transaction_date->format('Y-m-d H:i:s') : null,
            'description' => $goal->description
        ];
    }
    
    // Sort: completed first, then by progress descending
    usort($goalDetails, function($a, $b) {
        if ($a['status'] === 'completed' && $b['status'] !== 'completed') return -1;
        if ($a['status'] !== 'completed' && $b['status'] === 'completed') return 1;
        return $b['progress_percentage'] - $a['progress_percentage'];
    });
    
    // ===== ACHIEVEMENTS =====
    $achievements = [];
    
    // Goals completed (100%)
    foreach ($completedGoals as $goal) {
        $achievements[] = [
            'type' => 'goal_completed',
            'icon' => '🏆',
            'title' => 'Goal Reached!',
            'description' => "Congratulations! The goal \"{$goal->name}\" has reached 100%",
            'goal_name' => $goal->name,
            'amount' => (float) $goal->target_amount,
            'date' => $goal->updated_at ? $goal->updated_at->format('Y-m-d') : null
        ];
    }
    
    // Goals at 75%+
    $almostComplete = $goals->filter(function($goal) {
        $progress = $goal->progress_percentage;
        return $progress >= 75 && $progress < 100;
    });
    
    foreach ($almostComplete as $goal) {
        $achievements[] = [
            'type' => 'milestone_75',
            'icon' => '🎯',
            'title' => 'Almost There!',
            'description' => "The goal \"{$goal->name}\" is already {$goal->progress_percentage}%",
            'goal_name' => $goal->name,
            'progress' => $goal->progress_percentage,
            'remaining' => (float) ($goal->target_amount - $goal->current_amount)
        ];
    }
    
    // Goals at 50%+
    $halfwayGoals = $goals->filter(function($goal) {
        $progress = $goal->progress_percentage;
        return $progress >= 50 && $progress < 75;
    });
    
    foreach ($halfwayGoals as $goal) {
        $achievements[] = [
            'type' => 'milestone_50',
            'icon' => '⭐',
            'title' => 'Halfway There',
            'description' => "The goal \"{$goal->name}\" has reached {$goal->progress_percentage}%",
            'goal_name' => $goal->name,
            'progress' => $goal->progress_percentage
        ];
    }
    
    // ===== SAVINGS TIPS (Saran Khusus) =====
    $tips = [];
    
    // Tip based on progress
    if ($overallProgress < 25) {
        $tips[] = [
            'icon' => '💡',
            'tip' => 'Start saving bit by bit consistently. Consistency is key!'
        ];
    } else if ($overallProgress >= 75) {
        $tips[] = [
            'icon' => '🚀',
            'tip' => 'Amazing! You are almost at your target. Keep it up!'
        ];
    }
    
    // Tip for goals near deadline
    foreach ($goalDetails as $goal) {
        if ($goal['status'] === 'active' && $goal['days_remaining'] !== null) {
            if ($goal['days_remaining'] <= 7 && $goal['days_remaining'] > 0) {
                $tips[] = [
                    'icon' => '⏰',
                    'tip' => "Goal \"{$goal['name']}\" deadline in {$goal['days_remaining']} days. Remaining: Rp " . number_format($goal['remaining_amount'], 0, ',', '.')
                ];
            } else if ($goal['days_remaining'] < 0) {
                $tips[] = [
                    'icon' => '⚠️',
                    'tip' => "Goal \"{$goal['name']}\" has passed its deadline. Consider updating the deadline or increasing deposits."
                ];
            }
        }
    }
    
    // ===== MONTHLY BREAKDOWN (for current year) =====
    $monthlyBreakdown = [];
    $currentYear = date('Y');
    
    for ($month = 1; $month <= 12; $month++) {
        $monthStart = "$currentYear-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
        $monthEnd = date('Y-m-t', strtotime($monthStart));
        
        $monthlyAmount = Transaction::whereHas('goal', function($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->whereBetween('transaction_date', [$monthStart . ' 00:00:00', $monthEnd . ' 23:59:59'])
            ->sum('amount');
        
        $monthlyCount = Transaction::whereHas('goal', function($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->whereBetween('transaction_date', [$monthStart . ' 00:00:00', $monthEnd . ' 23:59:59'])
            ->count();
        
        if ($monthlyAmount > 0 || $month <= (int)date('m')) {
            $monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 
                          'July', 'August', 'September', 'October', 'November', 'December'];
            
            $monthlyBreakdown[] = [
                'month' => $monthNames[$month],
                'month_number' => $month,
                'year' => (int) $currentYear,
                'amount' => (float) $monthlyAmount,
                'deposit_count' => $monthlyCount
            ];
        }
    }
    
    // ===== RESPONSE =====
    $report = [
        'report_date' => date('Y-m-d H:i:s'),
        'report_title' => 'GoalMoney Savings Progress Report',
        'period' => [
            'start' => $startDate,
            'end' => $endDate
        ],
        'summary' => [
            'total_goals' => $totalGoals,
            'completed_goals' => $completedCount,
            'active_goals' => $activeCount,
            'total_saved' => (float) $totalSaved,
            'total_target' => (float) $totalTarget,
            'overall_progress' => $overallProgress,
            'period_savings' => (float) $periodSavings,
            'total_deposits' => $totalDeposits,
            'total_withdrawals' => $totalWithdrawals
        ],
        'goals' => $goalDetails,
        'achievements' => $achievements,
        'tips' => $tips,
        'monthly_breakdown' => $monthlyBreakdown
    ];
    
    Response::success('Report generated successfully', $report);
    
} catch (Exception $e) {
    Response::error('Failed to generate report: ' . $e->getMessage(), 500);
}
?>
