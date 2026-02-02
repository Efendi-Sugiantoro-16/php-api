<?php
// app/models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'available_balance'
    ];

    protected $hidden = [
        'password'
    ];

    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    // Relationships
    public function goals()
    {
        return $this->hasMany(Goal::class);
    }

    public function tokens()
    {
        return $this->hasMany(Token::class);
    }

    // Hash password automatically
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = password_hash($value, PASSWORD_BCRYPT);
    }

    // Verify password
    public function verifyPassword($password)
    {
        return password_verify($password, $this->password);
    }

    // Method untuk menambah available balance
    public function addAvailableBalance($amount)
    {
        $balance = isset($this->available_balance) ? $this->available_balance : 0;
        $this->available_balance = $balance + $amount;
        $this->save();
    }

    // Method untuk mengurangi available balance
    public function subtractAvailableBalance($amount)
    {
        $balance = isset($this->available_balance) ? $this->available_balance : 0;
        if ($balance < $amount) {
            throw new \Exception('Insufficient available balance');
        }
        $this->available_balance = $balance - $amount;
        $this->save();
    }
}
?>