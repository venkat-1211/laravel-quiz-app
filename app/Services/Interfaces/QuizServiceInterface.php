<?php

namespace App\Services\Interfaces;

use App\DTOs\QuizDTO;
use Illuminate\Pagination\LengthAwarePaginator;

interface QuizServiceInterface
{
    public function getPublishedQuizzes(array $filters = []): LengthAwarePaginator;
    public function getQuizBySlug(string $slug): ?QuizDTO;
    public function createQuiz(QuizDTO $quizDTO): QuizDTO;
    public function updateQuiz(int $id, QuizDTO $quizDTO): QuizDTO;
    public function deleteQuiz(int $id): bool;
    public function publishQuiz(int $id): bool;
    public function unpublishQuiz(int $id): bool;
    public function getQuizForAttempt(int $id, int $userId): array;
    public function getPopularQuizzes(int $limit = 5): array;
}