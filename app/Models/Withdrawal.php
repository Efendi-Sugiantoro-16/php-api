<?php
// app/Models/Withdrawal.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model {
    protected $table = 'withdrawals';
    
    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    
    // Valid methods
    const VALID_METHODS = ['dana', 'gopay', 'bank_transfer', 'ovo', 'shopeepay'];
    
    protected $fillable = [
        'user_id',
        'amount',
        'method',
        'account_number',
        'status',
        'notes'
    ];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    
    // Relationships
    public function user() {
        return $this->belongsTo(User::class);
    }
    
    // Check if status is pending
    public function isPending() {
        return $this->status === self::STATUS_PENDING;
    }
    
    // Approve withdrawal
    public function approve($notes = null) {
        $this->status = self::STATUS_APPROVED;
        if ($notes) {
            $this->notes = $notes;
        }
        $this->save();
    }
    
    // Reject withdrawal
    public function reject($notes = null) {
        $this->status = self::STATUS_REJECTED;
        if ($notes) {
            $this->notes = $notes;
        }
        $this->save();
    }
    
    // Validate method
    public static function isValidMethod($method) {
        return in_array($method, self::VALID_METHODS);
    }

    // Process delayed approvals (Auto-Approve after 30 seconds)
    public static function processDelayedApprovals($userId) {
        // Find pending withdrawals older than 30 seconds
        $withdrawals = self::where('user_id', $userId)
                          ->where('status', self::STATUS_PENDING)
                          ->where('created_at', '<', \Carbon\Carbon::now()->subSeconds(30))
                          ->get();

        $processedCount = 0;

        foreach ($withdrawals as $withdrawal) {
            // Logic to approve and deduct balance
            try {
                // Get user goals to deduct balance
                $totalBalance = Goal::where('user_id', $userId)->sum('current_amount');
                
                if ($totalBalance >= $withdrawal->amount) {
                    $amountToDeduct = $withdrawal->amount;
                    $goals = Goal::where('user_id', $userId)
                                 ->where('current_amount', '>', 0)
                                 ->orderBy('current_amount', 'desc')
                                 ->get();
                    
                    foreach ($goals as $goal) {
                        if ($amountToDeduct <= 0) break;
                        
                        $deductFromThisGoal = min($goal->current_amount, $amountToDeduct);
                        $goal->subtractAmount($deductFromThisGoal);
                        $amountToDeduct -= $deductFromThisGoal;
                    }
                    
                    // Approve
                    $withdrawal->approve('Auto-approved by system');
                    
                    // Create Notification
                    \App\Models\Notification::createNotification(
                        $userId,
                        'Penarikan Berhasil',
                        'Penarikan dana sebesar Rp ' . number_format($withdrawal->amount, 0, ',', '.') . ' berhasil diproses.',
                        'withdrawal'
                    );
                    
                    $processedCount++;
                }
            } catch (\Exception $e) {
                error_log("Auto-Approval Error: " . $e->getMessage());
            }
        }
        
        return $processedCount;
    }
}
?>
