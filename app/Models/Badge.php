<?php
// app/Models/Badge.php
// Model untuk Badge system

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Badge extends Model {
    protected $table = 'badges';
    
    protected $fillable = [
        'code',
        'name',
        'description',
        'icon',
        'requirement_type',
        'requirement_value'
    ];
    
    public $timestamps = false;
    const CREATED_AT = 'created_at';
    
    // Relationship: Users who have this badge
    public function users() {
        return $this->belongsToMany(User::class, 'user_badges', 'badge_id', 'user_id')
                    ->withPivot('earned_at');
    }
}
?>
