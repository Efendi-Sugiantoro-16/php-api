<?php
// app/models/Token.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Token extends Model {
    protected $table = 'tokens';
    
    protected $fillable = [
        'user_id',
        'token',
        'expires_at'
    ];
    
    protected $casts = [
        'expires_at' => 'datetime'
    ];
    
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;
    
    // Relationships
    public function user() {
        return $this->belongsTo(User::class);
    }
    
    // Generate new token
    public static function generateToken($userId) {
        // Delete existing tokens for this user
        static::where('user_id', $userId)->delete();
        
        // Create new token
        $tokenString = bin2hex(random_bytes(64));
        $expiryDays = env('TOKEN_EXPIRY_DAYS', 30);
        
        $token = static::create([
            'user_id' => $userId,
            'token' => $tokenString,
            'expires_at' => \Carbon\Carbon::now()->addDays($expiryDays)
        ]);
        
        return $token;
    }
    
    // Verify token
    public static function verify($tokenString) {
        $token = static::where('token', $tokenString)
                      ->where('expires_at', '>', \Carbon\Carbon::now())
                      ->first();
        
        return $token ? $token->user_id : null;
    }
    
    // Check if token is expired
    public function isExpired() {
        return $this->expires_at < \Carbon\Carbon::now();
    }
}
?>
