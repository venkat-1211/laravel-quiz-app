<?php

namespace App\DTOs;

use App\Models\Question;

class QuestionDTO
{
    public ?int $id;
    public int $quiz_id;
    public string $question_text;
    public array $options;
    public string $correct_answer;
    public ?string $explanation;
    public ?string $image_url;
    public ?string $video_url;
    public string $difficulty;
    public int $points;
    public int $order;
    public ?string $created_at;
    public ?string $updated_at;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? null;
        $this->quiz_id = $data['quiz_id'];
        $this->question_text = $data['question_text'];
        $this->options = $data['options'] ?? [];
        $this->correct_answer = $data['correct_answer'];
        $this->explanation = $data['explanation'] ?? null;
        $this->image_url = $data['image_url'] ?? null;
        $this->video_url = $data['video_url'] ?? null;
        $this->difficulty = $data['difficulty'] ?? 'medium';
        $this->points = $data['points'] ?? 10;
        $this->order = $data['order'] ?? 0;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }

    public static function fromModel(Question $question): self
    {
        return new self([
            'id' => $question->id,
            'quiz_id' => $question->quiz_id,
            'question_text' => $question->question_text,
            'options' => $question->options ?? [],
            'correct_answer' => $question->correct_answer,
            'explanation' => $question->explanation,
            'image_url' => $question->image_url,
            'video_url' => $question->video_url,
            'difficulty' => $question->difficulty,
            'points' => $question->points,
            'order' => $question->order,
            'created_at' => $question->created_at?->toDateTimeString(),
            'updated_at' => $question->updated_at?->toDateTimeString(),
        ]);
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'quiz_id' => $this->quiz_id,
            'question_text' => $this->question_text,
            'options' => $this->options,
            'correct_answer' => $this->correct_answer,
            'explanation' => $this->explanation,
            'image_url' => $this->image_url,
            'video_url' => $this->video_url,
            'difficulty' => $this->difficulty,
            'points' => $this->points,
            'order' => $this->order,
        ];
    }

    public function getFormattedOptions(): array
    {
        $formatted = [];
        foreach ($this->options as $key => $value) {
            $formatted[] = [
                'key' => $key,
                'value' => $value,
            ];
        }
        return $formatted;
    }

    public function getCorrectAnswerText(): ?string
    {
        return $this->options[$this->correct_answer] ?? null;
    }

    public function isCorrect(string $answer): bool
    {
        return $this->correct_answer === $answer;
    }

    public function validateCorrectAnswer(): bool
    {
        return isset($this->options[$this->correct_answer]) && 
               !empty($this->options[$this->correct_answer]);
    }
}