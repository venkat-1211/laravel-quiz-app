<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attempt extends Model
{
    use HasFactory;

    protected $table = 'attempts';

    protected $fillable = [
        'user_id',
        'quiz_id',
        'score',
        'total_questions',
        'correct_answers',
        'incorrect_answers',
        'skipped_answers',
        'percentage_score',
        'time_taken',
        'answers_summary',
        'status',
        'started_at',
        'completed_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'answers_summary' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function answers()
    {
        return $this->hasMany(AttemptAnswer::class);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByQuiz($query, $quizId)
    {
        return $query->where('quiz_id', $quizId);
    }

    public function getIsPassedAttribute()
    {
        return $this->percentage_score >= $this->quiz->passing_score;
    }

    public function getRemainingAttemptsAttribute()
    {
        if ($this->quiz->max_attempts === 0) {
            return PHP_INT_MAX;
        }
        
        $attemptsCount = Attempt::where('user_id', $this->user_id)
                                 ->where('quiz_id', $this->quiz_id)
                                 ->where('status', 'completed')
                                 ->count();
        
        return max(0, $this->quiz->max_attempts - $attemptsCount);
    }

    public function calculateScore()
    {
        $answers = $this->answers()->with('question')->get();
        
        $correctCount = 0;
        $incorrectCount = 0;
        $skippedCount = 0;
        $totalScore = 0;
        
        foreach ($answers as $answer) {
            if ($answer->selected_answer === null) {
                $skippedCount++;
            } elseif ($answer->is_correct) {
                $correctCount++;
                $totalScore += $answer->question->points ?? 10;
            } else {
                $incorrectCount++;
            }
        }
        
        $this->correct_answers = $correctCount;
        $this->incorrect_answers = $incorrectCount;
        $this->skipped_answers = $skippedCount;
        $this->total_questions = $answers->count();
        $this->score = $totalScore;
        $this->percentage_score = $this->total_questions > 0 
            ? round(($correctCount / $this->total_questions) * 100, 2) 
            : 0;
        
        $this->saveQuietly();
        
        return $this;
    }
}