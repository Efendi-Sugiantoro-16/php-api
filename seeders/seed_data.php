<?php


require_once __DIR__ . '/../bootstrap.php';

use App\Models\User;
use App\Models\Goal;
use App\Models\Transaction;

echo "Seeding database...\n\n";

try {
    // Create test user
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@goalmoney.com',
        'password' => 'password123'
    ]);
    echo "✓ User created: {$user->email}\n";

    // Create goals
    $goals = [
        [
            'name' => 'Laptop Baru',
            'target_amount' => 15000000,
            'deadline' => date('Y-m-d', strtotime('+6 months')),
            'description' => 'Untuk kuliah dan kerja'
        ],
        [
            'name' => 'Liburan ke Bali',
            'target_amount' => 10000000,
            'deadline' => date('Y-m-d', strtotime('+1 year')),
            'description' => 'Budget liburan keluarga'
        ],
        [
            'name' => 'Emergency Fund',
            'target_amount' => 30000000,
            'deadline' => null,
            'description' => 'Dana darurat 6 bulan pengeluaran'
        ]
    ];

    foreach ($goals as $goalData) {
        $goal = Goal::create([
            'user_id' => $user->id,
            'name' => $goalData['name'],
            'target_amount' => $goalData['target_amount'],
            'deadline' => $goalData['deadline'],
            'description' => $goalData['description']
        ]);

        echo "✓ Goal created: {$goal->name}\n";

        // Add some transactions for each goal
        $transactionCount = rand(2, 5);
        for ($i = 0; $i < $transactionCount; $i++) {
            $amount = rand(100000, 2000000);
            Transaction::create([
                'goal_id' => $goal->id,
                'amount' => $amount,
                'description' => 'Setoran ke-' . ($i + 1),
                'transaction_date' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'))
            ]);
        }

        echo "  ✓ Added {$transactionCount} transactions\n";
    }

    echo "\n✅ Seeding completed successfully!\n";
    echo "\n📧 Test account:\n";
    echo "   Email: test@goalmoney.com\n";
    echo "   Password: password123\n";

} catch (Exception $e) {
    echo "\n❌ Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>