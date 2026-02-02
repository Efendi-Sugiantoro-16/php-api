<?php
// app/models/Transaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = 'transactions';

    protected $fillable = [
        'goal_id',
        'amount',
        'method',
        'description',
        'transaction_date'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'datetime'
    ];

    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null; // No updated_at for transactions

    // Relationships
    public function goal()
    {
        return $this->belongsTo(Goal::class);
    }


    // Boot method removed - events require EventDispatcher setup
    // Goal updates are handled in controllers

}
?>