<?php
// app/models/Goal.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    protected $table = 'goals';

    protected $fillable = [
        'user_id',
        'name',
        'target_amount',
        'current_amount',
        'deadline',
        'description',
        'type'
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'deadline' => 'date'
    ];

    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Accessor untuk progress percentage
    public function getProgressPercentageAttribute()
    {
        if ($this->target_amount > 0) {
            return round(($this->current_amount / $this->target_amount) * 100, 2);
        }
        return 0;
    }

    // Method untuk menambah saldo
    public function addAmount($amount)
    {
        $remaining = $this->target_amount - $this->current_amount;
        $overflow = 0;

        if ($amount >= $remaining) {
            // Goal akan completed
            $this->current_amount = $this->target_amount;
            $overflow = $amount - $remaining;
        } else {
            // Deposit normal
            $this->current_amount += $amount;
        }

        $this->save();

        return [
            'completed' => $this->isCompleted(),
            'deposited_amount' => $amount - $overflow,
            'overflow_amount' => $overflow
        ];
    }

    // Method untuk cek apakah goal sudah completed
    public function isCompleted()
    {
        return $this->current_amount >= $this->target_amount;
    }

    // Method untuk mengurangi saldo
    public function subtractAmount($amount)
    {
        $this->current_amount -= $amount;
        $this->save();
    }
}
?>