<?php
// app/Models/Notification.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model {
    protected $table = 'notifications';
    
    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'is_read'
    ];
    
    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime'
    ];
    
    public $timestamps = false; // We only use created_at, handled by DB default
    
    // Relationships
    public function user() {
        return $this->belongsTo(User::class);
    }
    
    // Helper to create notification
    public static function createNotification($userId, $title, $message, $type = 'info') {
        // 1. Save to Database
        $notif = self::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type
        ]);

        // 2. Send Push Notification (Fire & Forget)
        try {
            // Topic pattern: user_{id} (Flutter app must subscribe to this)
            $topic = "user_" . $userId;
            \App\Helpers\FCMHelper::sendToTopic($topic, $title, $message, ['type' => $type]);
        } catch (\Exception $e) {
            error_log("FCM Failed: " . $e->getMessage());
        }

        return $notif;
    }
}
?>
