<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Achievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'points_required',
        'criteria',
    ];

    protected $casts = [
        'criteria' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($achievement) {
            $achievement->slug = $achievement->slug ?? Str::slug($achievement->name);
        });
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_achievements')
                    ->withPivot('earned_at')
                    ->withTimestamps();
    }

    public function checkAndAward(User $user)
    {
        $criteria = $this->criteria;
        
        switch ($criteria['type'] ?? null) {
            case 'quizzes_completed':
                $count = $user->attempts()
                              ->where('status', 'completed')
                              ->count();
                if ($count >= ($criteria['value'] ?? 0)) {
                    $this->awardTo($user);
                }
                break;
                
            case 'total_points':
                if ($user->total_points >= ($criteria['value'] ?? 0)) {
                    $this->awardTo($user);
                }
                break;
                
            case 'average_score':
                $average = $user->average_score;
                if ($average >= ($criteria['value'] ?? 0)) {
                    $this->awardTo($user);
                }
                break;
        }
    }

    public function awardTo(User $user)
    {
        if (!$user->achievements()->where('achievement_id', $this->id)->exists()) {
            $user->achievements()->attach($this->id, ['earned_at' => now()]);
            
            $leaderboard = $user->leaderboard;
            if ($leaderboard) {
                $badges = $leaderboard->badges ?? [];
                $badges[] = [
                    'id' => $this->id,
                    'name' => $this->name,
                    'icon' => $this->icon,
                    'earned_at' => now()->toDateTimeString(),
                ];
                $leaderboard->badges = $badges;
                $leaderboard->save();
            }
        }
    }
}