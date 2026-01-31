<?php
// fix_user_data.php - Script untuk memastikan user aktif punya data

require_once __DIR__ . '/bootstrap.php';

use App\Models\User;
use App\Models\Goal;
use App\Models\Transaction;
use App\Models\Token;

header('Content-Type: text/plain; charset=utf-8');

echo "=== FIX USER DATA ===\n\n";

// Dapatkan token terakhir (user yang sedang login)
$latestToken = Token::orderBy('id', 'desc')->first();

if (!$latestToken) {
    echo "ERROR: Tidak ada token di database!\n";
    exit;
}

$activeUserId = $latestToken->user_id;
$activeUser = User::find($activeUserId);

echo "User Aktif (dari token terakhir):\n";
echo "  ID: {$activeUserId}\n";
echo "  Nama: {$activeUser->name}\n";
echo "  Email: {$activeUser->email}\n\n";

// Cek goals untuk user ini
$goals = Goal::where('user_id', $activeUserId)->get();
echo "Goals untuk user ini: {$goals->count()}\n";

if ($goals->count() == 0) {
    echo "\n⚠️  USER INI BELUM PUNYA GOALS!\n";
    echo "Membuat sample goals...\n\n";
    
    // Buat sample goals
    $sampleGoals = [
        [
            'name' => 'Dana Darurat',
            'target_amount' => 5000000,
            'current_amount' => 1500000,
            'deadline' => date('Y-m-d', strtotime('+3 months')),
            'description' => 'Dana darurat 3 bulan pengeluaran',
            'type' => 'digital'
        ],
        [
            'name' => 'Liburan Bali',
            'target_amount' => 3000000,
            'current_amount' => 750000,
            'deadline' => date('Y-m-d', strtotime('+2 months')),
            'description' => 'Trip ke Bali',
            'type' => 'digital'
        ],
        [
            'name' => 'Beli Gadget',
            'target_amount' => 2000000,
            'current_amount' => 500000,
            'deadline' => date('Y-m-d', strtotime('+1 month')),
            'description' => 'Earbuds wireless',
            'type' => 'digital'
        ],
    ];
    
    foreach ($sampleGoals as $goalData) {
        $goal = new Goal();
        $goal->user_id = $activeUserId;
        $goal->name = $goalData['name'];
        $goal->target_amount = $goalData['target_amount'];
        $goal->current_amount = $goalData['current_amount'];
        $goal->deadline = $goalData['deadline'];
        $goal->description = $goalData['description'];
        $goal->type = $goalData['type'];
        $goal->save();
        
        echo "✓ Created goal: {$goalData['name']}\n";
        
        // Buat sample transactions untuk goal ini
        $amounts = [$goalData['current_amount'] * 0.4, $goalData['current_amount'] * 0.6];
        foreach ($amounts as $i => $amount) {
            $tx = new Transaction();
            $tx->goal_id = $goal->id;
            $tx->amount = $amount;
            $tx->method = 'transfer';
            $tx->description = 'Deposit ke-' . ($i + 1);
            $tx->transaction_date = date('Y-m-d H:i:s', strtotime("-$i days"));
            $tx->save();
            
            echo "  ✓ Transaction: Rp " . number_format($amount, 0, ',', '.') . "\n";
        }
    }
    
    echo "\n✅ Sample data created successfully!\n";
    echo "Total goals: 3\n";
    echo "Total tabungan: Rp 2.750.000\n";
} else {
    echo "\n✅ User sudah punya {$goals->count()} goals:\n";
    $totalSaved = 0;
    foreach ($goals as $goal) {
        echo "  - {$goal->name}: Rp " . number_format($goal->current_amount, 0, ',', '.') . " / Rp " . number_format($goal->target_amount, 0, ',', '.') . "\n";
        $totalSaved += $goal->current_amount;
    }
    echo "\nTotal tabungan: Rp " . number_format($totalSaved, 0, ',', '.') . "\n";
}

echo "\n=== DONE ===\n";
echo "Silakan refresh halaman Analytics di aplikasi!\n";
