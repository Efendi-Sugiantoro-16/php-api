<?php
// api/recommendations/index.php
// Smart Savings Recommendation API

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/cors.php';

use App\Models\Goal;
use App\Models\Transaction;
use App\Models\User;
use App\Helpers\Response;
use App\Middleware\Auth;

$userId = Auth::authenticate();

try {
    // Get user's active goals (not completed)
    $goals = Goal::where('user_id', $userId)
        ->whereRaw('current_amount < target_amount')
        ->orderBy('deadline', 'asc')
        ->get();

    $recommendations = [];

    foreach ($goals as $goal) {
        $remaining = $goal->target_amount - $goal->current_amount;
        $progress = $goal->progress_percentage;

        // Calculate days remaining
        $daysRemaining = null;
        $dailySuggestion = null;
        $weeklySuggestion = null;
        $urgency = 'normal';
        $status = 'on_track';

        if ($goal->deadline) {
            $deadline = new DateTime($goal->deadline);
            $today = new DateTime();
            $diff = $today->diff($deadline);
            $daysRemaining = $diff->invert ? -$diff->days : $diff->days;

            if ($daysRemaining > 0) {
                $dailySuggestion = ceil($remaining / $daysRemaining);
                $weeklySuggestion = ceil($remaining / ceil($daysRemaining / 7));

                // Determine urgency
                if ($daysRemaining <= 7) {
                    $urgency = 'critical';
                } elseif ($daysRemaining <= 30) {
                    $urgency = 'high';
                } elseif ($daysRemaining <= 90) {
                    $urgency = 'medium';
                }

                // Expected progress based on deadline
                $totalDays = (new DateTime($goal->created_at))->diff($deadline)->days;
                $elapsedDays = (new DateTime($goal->created_at))->diff($today)->days;
                $expectedProgress = $totalDays > 0 ? ($elapsedDays / $totalDays) * 100 : 0;

                if ($progress >= $expectedProgress + 10) {
                    $status = 'ahead';
                } elseif ($progress < $expectedProgress - 10) {
                    $status = 'behind';
                }
            } else {
                $urgency = 'overdue';
                $status = 'overdue';
            }
        } else {
            // No deadline - suggest based on average deposit
            $avgDeposit = Transaction::where('goal_id', $goal->id)
                ->avg('amount') ?? 50000;

            $dailySuggestion = $avgDeposit;
            $weeklySuggestion = $avgDeposit * 7;
        }

        // Generate tip based on status
        $tip = generateTip($status, $progress, $urgency, $daysRemaining);

        $recommendations[] = [
            'goal_id' => $goal->id,
            'goal_name' => $goal->name,
            'target_amount' => (float) $goal->target_amount,
            'current_amount' => (float) $goal->current_amount,
            'remaining' => $remaining,
            'progress' => $progress,
            'deadline' => $goal->deadline,
            'days_remaining' => $daysRemaining,
            'daily_suggestion' => $dailySuggestion,
            'weekly_suggestion' => $weeklySuggestion,
            'urgency' => $urgency,
            'status' => $status,
            'tip' => $tip,
        ];
    }

    // Sort by urgency priority
    usort($recommendations, function ($a, $b) {
        $urgencyOrder = ['critical' => 0, 'overdue' => 1, 'high' => 2, 'medium' => 3, 'normal' => 4];
        return ($urgencyOrder[$a['urgency']] ?? 5) - ($urgencyOrder[$b['urgency']] ?? 5);
    });

    // Global recommendation
    $globalTip = generateGlobalTip($recommendations);

    Response::success('Recommendations retrieved', [
        'recommendations' => $recommendations,
        'count' => count($recommendations),
        'global_tip' => $globalTip,
    ]);

} catch (Exception $e) {
    Response::error($e->getMessage(), 500);
}

/**
 * Generate tip based on goal status
 */
function generateTip($status, $progress, $urgency, $daysRemaining)
{
    if ($status === 'overdue') {
        return '⚠️ Goal sudah melewati deadline. Pertimbangkan untuk memperpanjang atau fokuskan tabungan ke goal ini.';
    }

    if ($urgency === 'critical') {
        return "🚨 Hanya $daysRemaining hari lagi! Tingkatkan frekuensi menabung untuk mencapai target.";
    }

    if ($status === 'ahead') {
        return '🎉 Kamu menabung lebih cepat dari target! Pertahankan momentum ini.';
    }

    if ($status === 'behind') {
        return '📈 Progress sedikit tertinggal. Coba tambah nominal atau frekuensi nabung.';
    }

    if ($progress >= 75) {
        return '💪 Hampir selesai! Tinggal sedikit lagi untuk mencapai goalmu.';
    }

    if ($progress >= 50) {
        return '⭐ Bagus! Sudah setengah jalan. Terus konsisten menabung.';
    }

    if ($progress >= 25) {
        return '🌱 Progress bagus! Terus tingkatkan kebiasaan menabungmu.';
    }

    return '💡 Mulai dari yang kecil! Konsistensi lebih penting dari nominal besar.';
}

/**
 * Generate global tip
 */
function generateGlobalTip($recommendations)
{
    if (empty($recommendations)) {
        return '🎯 Belum ada goal aktif. Buat goal baru untuk mulai menabung!';
    }

    // Check for critical goals
    $criticalCount = count(array_filter($recommendations, fn($r) => $r['urgency'] === 'critical'));
    if ($criticalCount > 0) {
        return "🚨 Ada $criticalCount goal dengan deadline kurang dari 7 hari. Fokuskan tabunganmu!";
    }

    // Check for behind goals
    $behindCount = count(array_filter($recommendations, fn($r) => $r['status'] === 'behind'));
    if ($behindCount > 0) {
        return "📊 Ada $behindCount goal yang progress-nya tertinggal. Tingkatkan frekuensi nabung!";
    }

    // Check for ahead goals
    $aheadCount = count(array_filter($recommendations, fn($r) => $r['status'] === 'ahead'));
    if ($aheadCount === count($recommendations)) {
        return '🌟 Semua goal on track! Kamu menabung dengan sangat baik. Pertahankan!';
    }

    return '💰 Terus konsisten menabung! Setiap rupiah membawamu lebih dekat ke tujuan.';
}
