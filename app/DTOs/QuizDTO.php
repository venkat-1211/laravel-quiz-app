<?php

namespace App\DTOs;

use App\Models\Quiz;

class QuizDTO
{
    public ?int $id;
    public string $title;
    public string $slug;
    public string $description;
    public ?string $featured_image;
    public int $category_id;
    public string $difficulty;
    public int $time_limit;
    public int $passing_score;
    public int $total_questions;
    public int $max_attempts;
    public bool $is_published;
    public bool $shuffle_questions;
    public bool $show_answers;
    public int $points_per_question;
    public ?string $published_at;
    public ?array $category;
    public ?array $questions;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? null;
        $this->title = $data['title'];
        $this->slug = $data['slug'] ?? '';
        $this->description = $data['description'];
        $this->featured_image = $data['featured_image'] ?? null;
        $this->category_id = $data['category_id'];
        $this->difficulty = $data['difficulty'];
        $this->time_limit = $data['time_limit'];
        $this->passing_score = $data['passing_score'] ?? 70;
        $this->total_questions = $data['total_questions'] ?? 0;
        $this->max_attempts = $data['max_attempts'] ?? 0;
        $this->is_published = $data['is_published'] ?? false;
        $this->shuffle_questions = $data['shuffle_questions'] ?? false;
        $this->show_answers = $data['show_answers'] ?? true;
        $this->points_per_question = $data['points_per_question'] ?? 10;
        $this->published_at = $data['published_at'] ?? null;
        $this->category = $data['category'] ?? null;
        $this->questions = $data['questions'] ?? null;
    }

    public static function fromModel(Quiz $quiz): self
    {
        return new self([
            'id' => $quiz->id,
            'title' => $quiz->title,
            'slug' => $quiz->slug,
            'description' => $quiz->description,
            'featured_image' => $quiz->featured_image,
            'category_id' => $quiz->category_id,
            'difficulty' => $quiz->difficulty,
            'time_limit' => $quiz->time_limit,
            'passing_score' => $quiz->passing_score,
            'total_questions' => $quiz->total_questions,
            'max_attempts' => $quiz->max_attempts,
            'is_published' => $quiz->is_published,
            'shuffle_questions' => $quiz->shuffle_questions,
            'show_answers' => $quiz->show_answers,
            'points_per_question' => $quiz->points_per_question,
            'published_at' => $quiz->published_at?->toDateTimeString(),
            'category' => $quiz->category ? [
                'id' => $quiz->category->id,
                'name' => $quiz->category->name,
                'slug' => $quiz->category->slug,
            ] : null,
            'questions' => $quiz->questions ? $quiz->questions->map(fn($q) => [
                'id' => $q->id,
                'text' => $q->question_text,
                'options' => $q->options,
                'correct_answer' => $q->correct_answer,
                'explanation' => $q->explanation,
                'points' => $q->points,
            ])->toArray() : null,
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
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'featured_image' => $this->featured_image,
            'category_id' => $this->category_id,
            'difficulty' => $this->difficulty,
            'time_limit' => $this->time_limit,
            'passing_score' => $this->passing_score,
            'total_questions' => $this->total_questions,
            'max_attempts' => $this->max_attempts,
            'is_published' => $this->is_published,
            'shuffle_questions' => $this->shuffle_questions,
            'show_answers' => $this->show_answers,
            'points_per_question' => $this->points_per_question,
            'published_at' => $this->published_at,
        ];
    }
}