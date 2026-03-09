<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Quiz extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'featured_image',
        'category_id',
        'difficulty',
        'time_limit',
        'passing_score',
        'total_questions',
        'max_attempts',
        'is_published',
        'shuffle_questions',
        'show_answers',
        'points_per_question',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'shuffle_questions' => 'boolean',
        'show_answers' => 'boolean',
        'published_at' => 'datetime',
        'time_limit' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($quiz) {
            if (empty($quiz->slug)) {
                $quiz->slug = Str::slug($quiz->title);
            }
        });
        
        static::updating(function ($quiz) {
            if ($quiz->isDirty('title') && empty($quiz->slug)) {
                $quiz->slug = Str::slug($quiz->title);
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    public function attempts()
    {
        return $this->hasMany(Attempt::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
                     ->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('title', 'like', "%{$search}%")
                     ->orWhere('description', 'like', "%{$search}%");
    }

    public function getFormattedTimeLimitAttribute()
    {
        $hours = floor($this->time_limit / 60);
        $minutes = $this->time_limit % 60;
        
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        
        return "{$minutes} min";
    }

    public function getDifficultyBadgeAttribute()
    {
        $colors = [
            'beginner' => 'success',
            'intermediate' => 'info',
            'advanced' => 'warning',
            'expert' => 'danger',
        ];
        
        return [
            'text' => ucfirst($this->difficulty),
            'color' => $colors[$this->difficulty] ?? 'secondary',
        ];
    }

    public function updateTotalQuestions()
    {
        $this->total_questions = $this->questions()->count();
        $this->saveQuietly();
    }
}