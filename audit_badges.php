<?php
require_once __DIR__ . '/bootstrap.php';
use Illuminate\Database\Capsule\Manager as Capsule;

try {
    $report = "--- User Badge Distribution Report ---\n";
    
    $users = Capsule::table('users')->select('id', 'name', 'email')->get();
    $allBadges = Capsule::table('badges')->get()->keyBy('id');
    
    foreach ($users as $user) {
        $report .= "User: [{$user->id}] {$user->name} ({$user->email})\n";
        
        $userBadges = Capsule::table('user_badges')
            ->where('user_id', $user->id)
            ->get();
            
        $report .= "Badges Earned (" . $userBadges->count() . "):\n";
        
        if ($userBadges->isEmpty()) {
            $report .= "  (None)\n";
        } else {
            foreach ($userBadges as $ub) {
                $badge = $allBadges->get($ub->badge_id);
                $badgeName = $badge ? $badge->name : "Unknown [ID: {$ub->badge_id}]";
                $report .= "  - {$badgeName} (Earned at: {$ub->earned_at})\n";
            }
        }
        $report .= "--------------------------------------\n";
    }
    
    file_put_contents(__DIR__ . '/audit_report.txt', $report);
    echo "Report generated successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
