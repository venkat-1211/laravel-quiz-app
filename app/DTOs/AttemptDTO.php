<?php

namespace App\DTOs;

use App\Models\Attempt;

class AttemptDTO
{
    public ?int $id;
    public int $user_id;
    public int $quiz_id;
    public int $score;
    public int $total_questions;
    public int $correct_answers;
    public int $incorrect_answers;
    public int $skipped_answers;
    public float $percentage_score;
    public int $time_taken;
    public ?array $answers_summary;
    public string $status;
    public string $started_at;
    public ?string $completed_at;
    public ?array $quiz;
    public ?array $answers;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? null;
        $this->user_id = $data['user_id'];
        $this->quiz_id = $data['quiz_id'];
        $this->score = $data['score'] ?? 0;
        $this->total_questions = $data['total_questions'] ?? 0;
        $this->correct_answers = $data['correct_answers'] ?? 0;
        $this->incorrect_answers = $data['incorrect_answers'] ?? 0;
        $this->skipped_answers = $data['skipped_answers'] ?? 0;
        $this->percentage_score = $data['percentage_score'] ?? 0.0;
        $this->time_taken = $data['time_taken'] ?? 0;
        $this->answers_summary = $data['answers_summary'] ?? null;
        $this->status = $data['status'];
        $this->started_at = $data['started_at'];
        $this->completed_at = $data['completed_at'] ?? null;
        $this->quiz = $data['quiz'] ?? null;
        $this->answers = $data['answers'] ?? null;
    }

    public static function fromModel(Attempt $attempt): self
    {
        // Ensure scores are calculated if attempt is completed
        if ($attempt->status === 'completed' && $attempt->correct_answers === 0 && $attempt->answers()->count() > 0) {
            $attempt->calculateScore();
        }
        
        return new self([
            'id' => $attempt->id,
            'user_id' => $attempt->user_id,
            'quiz_id' => $attempt->quiz_id,
            'score' => $attempt->score ?? 0,
            'total_questions' => $attempt->total_questions ?? 0,
            'correct_answers' => $attempt->correct_answers ?? 0,
            'incorrect_answers' => $attempt->incorrect_answers ?? 0,
            'skipped_answers' => $attempt->skipped_answers ?? 0,
            'percentage_score' => $attempt->percentage_score ?? 0.0,
            'time_taken' => (int) ($attempt->time_taken ?? 0),
            'answers_summary' => $attempt->answers_summary,
            'status' => $attempt->status,
            'started_at' => $attempt->started_at->toDateTimeString(),
            'completed_at' => $attempt->completed_at?->toDateTimeString(),
            'quiz' => $attempt->quiz ? [
                'id' => $attempt->quiz->id,
                'title' => $attempt->quiz->title,
                'slug' => $attempt->quiz->slug,
                'description' => $attempt->quiz->description,
                'passing_score' => $attempt->quiz->passing_score,
            ] : null,
            'answers' => $attempt->answers && $attempt->answers->count() > 0 ? $attempt->answers->map(fn($a) => [
                'id' => $a->id,
                'question_id' => $a->question_id,
                'question_text' => $a->question->question_text ?? 'Question',
                'selected_answer' => $a->selected_answer,
                'correct_answer' => $a->question->correct_answer ?? '',
                'is_correct' => $a->is_correct,
                'explanation' => $a->question->explanation ?? '',
                'options' => $a->question->options ?? [],
                'time_spent' => $a->time_spent,
                'is_flagged' => $a->is_flagged,
            ])->toArray() : null,
        ]);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'quiz_id' => $this->quiz_id,
            'score' => $this->score,
            'total_questions' => $this->total_questions,
            'correct_answers' => $this->correct_answers,
            'incorrect_answers' => $this->incorrect_answers,
            'skipped_answers' => $this->skipped_answers,
            'percentage_score' => $this->percentage_score,
            'time_taken' => $this->time_taken,
            'answers_summary' => $this->answers_summary,
            'status' => $this->status,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
        ];
    }
}