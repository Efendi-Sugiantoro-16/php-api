<?php
// api/analytics/summary.php
// API untuk data agregasi dan statistik

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Goal;
use App\Models\Transaction;
use App\Helpers\Response;
use App\Middleware\Auth;

$userId = Auth::authenticate();

try {
    $year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
    
    // 1. Monthly Savings Trend (untuk Line Chart)
    $monthlyTrend = [];
    for ($m = 1; $m <= 12; $m++) {
        $monthData = Transaction::whereHas('goal', function($q) use ($userId) {
            $q->where('user_id', $userId);
        })
        ->whereYear('transaction_date', $year)
        ->whereMonth('transaction_date', $m)
        ->selectRaw('SUM(amount) as total, COUNT(*) as count')
        ->first();
        
        $monthlyTrend[] = [
            'month' => $m,
            'month_name' => getMonthName($m),
            'month_short' => getMonthShort($m),
            'total' => (float) ($monthData->total ?? 0),
            'count' => (int) ($monthData->count ?? 0),
        ];
    }
    
    // 2. Goal Category Distribution (untuk Pie Chart)
    // Group by goal type or use custom categories based on target amount
    $goals = Goal::where('user_id', $userId)->get();
    $categoryDistribution = [
        'small' => ['label' => '< 500rb', 'count' => 0, 'total_saved' => 0],
        'medium' => ['label' => '500rb - 2jt', 'count' => 0, 'total_saved' => 0],
        'large' => ['label' => '2jt - 10jt', 'count' => 0, 'total_saved' => 0],
        'mega' => ['label' => '> 10jt', 'count' => 0, 'total_saved' => 0],
    ];
    
    foreach ($goals as $goal) {
        $target = $goal->target_amount;
        $saved = $goal->current_amount;
        
        if ($target < 500000) {
            $categoryDistribution['small']['count']++;
            $categoryDistribution['small']['total_saved'] += $saved;
        } elseif ($target < 2000000) {
            $categoryDistribution['medium']['count']++;
            $categoryDistribution['medium']['total_saved'] += $saved;
        } elseif ($target < 10000000) {
            $categoryDistribution['large']['count']++;
            $categoryDistribution['large']['total_saved'] += $saved;
        } else {
            $categoryDistribution['mega']['count']++;
            $categoryDistribution['mega']['total_saved'] += $saved;
        }
    }
    
    // 3. Goal Progress Comparison (untuk Bar Chart)
    $goalComparison = $goals->map(function($goal) {
        return [
            'id' => $goal->id,
            'name' => strlen($goal->name) > 15 
                ? substr($goal->name, 0, 15) . '...' 
                : $goal->name,
            'full_name' => $goal->name,
            'target' => (float) $goal->target_amount,
            'current' => (float) $goal->current_amount,
            'progress' => $goal->progress_percentage,
            'is_completed' => $goal->isCompleted(),
        ];
    })->sortByDesc('progress')->values()->take(6);
    
    // 4. Summary Stats
    $totalSaved = Goal::where('user_id', $userId)->sum('current_amount');
    $totalTarget = Goal::where('user_id', $userId)->sum('target_amount');
    $completedGoals = Goal::where('user_id', $userId)
        ->whereRaw('current_amount >= target_amount')
        ->count();
    $activeGoals = Goal::where('user_id', $userId)
        ->whereRaw('current_amount < target_amount')
        ->count();
    $totalDeposits = Transaction::whereHas('goal', function($q) use ($userId) {
        $q->where('user_id', $userId);
    })->count();
    
    // Average monthly saving
    $avgMonthlySaving = array_sum(array_column($monthlyTrend, 'total')) / 12;
    
    // Best month
    $bestMonth = collect($monthlyTrend)->sortByDesc('total')->first();
    
    Response::success('Analytics data retrieved', [
        'year' => $year,
        'monthly_trend' => $monthlyTrend,
        'category_distribution' => array_values($categoryDistribution),
        'goal_comparison' => $goalComparison,
        'summary' => [
            'total_saved' => (float) $totalSaved,
            'total_target' => (float) $totalTarget,
            'overall_progress' => $totalTarget > 0 
                ? round(($totalSaved / $totalTarget) * 100, 1) 
                : 0,
            'completed_goals' => $completedGoals,
            'active_goals' => $activeGoals,
            'total_deposits' => $totalDeposits,
            'avg_monthly_saving' => round($avgMonthlySaving, 0),
            'best_month' => $bestMonth,
        ],
    ]);
    
} catch (Exception $e) {
    Response::error($e->getMessage(), 500);
}

function getMonthName($month) {
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
        4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
        10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    return $months[$month] ?? '';
}

function getMonthShort($month) {
    $months = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar',
        4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep',
        10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];
    return $months[$month] ?? '';
}
