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
}
?>
