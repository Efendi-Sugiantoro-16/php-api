<?php
// api/analytics/streak.php
// API untuk mendapatkan streak data dan calendar heatmap

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Goal;
use App\Models\Transaction;
use App\Helpers\Response;
use App\Middleware\Auth;

$userId = Auth::authenticate();

try {
    // Get year & month parameter (default: current)
    $year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
    $month = isset($_GET['month']) ? (int) $_GET['month'] : null;

    // Get all deposit dates for the user in the specified period
    $query = Transaction::whereHas('goal', function ($q) use ($userId) {
        $q->where('user_id', $userId);
    })->whereYear('transaction_date', $year);

    if ($month) {
        $query->whereMonth('transaction_date', $month);
    }

    // Get daily deposits aggregated
    $deposits = $query
        ->selectRaw('DATE(transaction_date) as date, SUM(amount) as total_amount, COUNT(*) as deposit_count')
        ->groupBy('date')
        ->orderBy('date', 'asc')
        ->get();

    // Format as calendar data
    $calendarData = [];
    $totalDeposits = 0;
    $totalAmount = 0;

    foreach ($deposits as $deposit) {
        $calendarData[$deposit->date] = [
            'date' => $deposit->date,
            'amount' => (float) $deposit->total_amount,
            'count' => (int) $deposit->deposit_count,
            'intensity' => calculateIntensity($deposit->total_amount),
        ];
        $totalDeposits += $deposit->deposit_count;
        $totalAmount += $deposit->total_amount;
    }

    // Calculate streak
    $streakData = calculateStreak($userId);

    // Get monthly summary
    $monthlySummary = [];
    for ($m = 1; $m <= 12; $m++) {
        $monthData = Transaction::whereHas('goal', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $m)
            ->selectRaw('SUM(amount) as total, COUNT(*) as count')
            ->first();

        $monthlySummary[] = [
            'month' => $m,
            'month_name' => getMonthName($m),
            'total' => (float) ($monthData->total ?? 0),
            'count' => (int) ($monthData->count ?? 0),
        ];
    }

    Response::success('Streak data retrieved', [
        'year' => $year,
        'month' => $month,
        'calendar' => $calendarData,
        'streak' => $streakData,
        'monthly_summary' => $monthlySummary,
        'stats' => [
            'total_deposits' => $totalDeposits,
            'total_amount' => $totalAmount,
            'active_days' => count($calendarData),
        ]
    ]);

} catch (Exception $e) {
    Response::error($e->getMessage(), 500);
}

/**
 * Calculate intensity level (1-4) based on amount
 */
function calculateIntensity($amount)
{
    if ($amount >= 1000000)
        return 4; // ≥ 1 juta
    if ($amount >= 500000)
        return 3;  // ≥ 500rb
    if ($amount >= 100000)
        return 2;  // ≥ 100rb
    return 1;                          // < 100rb
}

/**
 * Calculate streak
 */
function calculateStreak($userId)
{
    $deposits = Transaction::whereHas('goal', function ($q) use ($userId) {
        $q->where('user_id', $userId);
    })
        ->selectRaw('DATE(transaction_date) as deposit_date')
        ->groupBy('deposit_date')
        ->orderBy('deposit_date', 'desc')
        ->pluck('deposit_date')
        ->toArray();

    if (empty($deposits)) {
        return [
            'current' => 0,
            'longest' => 0,
            'last_deposit' => null,
            'is_active_today' => false,
        ];
    }

    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $isActiveToday = ($deposits[0] == $today);

    // Calculate current streak
    $currentStreak = 0;
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
    $longestStreak = 1;
    $tempStreak = 1;
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
        'longest' => $longestStreak,
        'last_deposit' => $deposits[0],
        'is_active_today' => $isActiveToday,
    ];
}

/**
 * Get Indonesian month name
 */
function getMonthName($month)
{
    $months = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember'
    ];
    return $months[$month] ?? '';
}
