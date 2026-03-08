<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Question extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'quiz_id',
        'question_text',
        'options',
        'correct_answer',
        'explanation',
        'image_url',
        'video_url',
        'difficulty',
        'points',
        'order',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function attemptAnswers()
    {
        return $this->hasMany(AttemptAnswer::class);
    }

    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    public function getFormattedOptionsAttribute()
    {
        $options = [];
        foreach ($this->options as $key => $value) {
            $options[] = [
                'key' => $key,
                'value' => $value,
            ];
        }
        return $options;
    }

    public function isCorrect($answer)
    {
        return $this->correct_answer === $answer;
    }

    public function getCorrectAnswerTextAttribute()
    {
        return $this->options[$this->correct_answer] ?? null;
    }
}