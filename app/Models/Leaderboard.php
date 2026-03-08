<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Leaderboard extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_score',
        'quizzes_completed',
        'total_attempts',
        'average_score',
        'total_points',
        'rank',
        'weekly_rank',
        'monthly_rank',
        'badges',
    ];

    protected $casts = [
        'badges' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeTopRanked($query, $limit = 10)
    {
        return $query->with('user')
                     ->orderBy('rank')
                     ->limit($limit);
    }

    public function scopeWeeklyTop($query, $limit = 10)
    {
        return $query->with('user')
                     ->orderBy('weekly_rank')
                     ->limit($limit);
    }

    public function updateRank()
    {
        // Update global rank
        $this->rank = Leaderboard::where('total_points', '>', $this->total_points)->count() + 1;
        
        // Update weekly rank
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();
        
        $weeklyPoints = Attempt::where('user_id', $this->user_id)
                               ->where('status', 'completed')
                               ->whereBetween('completed_at', [$startOfWeek, $endOfWeek])
                               ->sum('score');
        
        $this->weekly_rank = Leaderboard::whereHas('user', function($query) use ($startOfWeek, $endOfWeek) {
            $query->whereHas('attempts', function($q) use ($startOfWeek, $endOfWeek) {
                $q->where('status', 'completed')
                  ->whereBetween('completed_at', [$startOfWeek, $endOfWeek]);
            });
        })->withSum(['attempts' => function($query) use ($startOfWeek, $endOfWeek) {
            $query->where('status', 'completed')
                  ->whereBetween('completed_at', [$startOfWeek, $endOfWeek]);
        }], 'score')
        ->having('attempts_sum_score', '>', $weeklyPoints)
        ->count() + 1;
        
        $this->saveQuietly();
    }
}