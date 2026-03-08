<?php

namespace App\Services;

use App\DTOs\QuizDTO;
use App\Services\Interfaces\QuizServiceInterface;
use App\Repositories\Interfaces\QuizRepositoryInterface;
use App\Repositories\Interfaces\QuestionRepositoryInterface;
use App\Repositories\Interfaces\AttemptRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class QuizService implements QuizServiceInterface
{
    public function __construct(
        private QuizRepositoryInterface $quizRepository,
        private QuestionRepositoryInterface $questionRepository,
        private AttemptRepositoryInterface $attemptRepository
    ) {}

    public function getPublishedQuizzes(array $filters = []): LengthAwarePaginator
    {
        return $this->quizRepository->getAllPublished($filters, 12);
    }

    public function getQuizBySlug(string $slug): ?QuizDTO
    {
        $quiz = $this->quizRepository->findBySlug($slug);
        
        if (!$quiz) {
            return null;
        }
        
        return QuizDTO::fromModel($quiz);
    }

    public function createQuiz(QuizDTO $quizDTO): QuizDTO
    {
        DB::beginTransaction();
        try {
            $quiz = $this->quizRepository->create($quizDTO->toArray());
            DB::commit();
            
            return QuizDTO::fromModel($quiz);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateQuiz(int $id, QuizDTO $quizDTO): QuizDTO
    {
        DB::beginTransaction();
        try {
            $quiz = $this->quizRepository->update($id, $quizDTO->toArray());
            DB::commit();
            
            return QuizDTO::fromModel($quiz);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteQuiz(int $id): bool
    {
        DB::beginTransaction();
        try {
            $result = $this->quizRepository->delete($id);
            DB::commit();
            
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function publishQuiz(int $id): bool
    {
        $quiz = $this->quizRepository->findById($id);
        
        if (!$quiz) {
            return false;
        }
        
        if ($quiz->questions()->count() === 0) {
            throw new \Exception('Cannot publish quiz with no questions');
        }
        
        DB::beginTransaction();
        try {
            $quiz->is_published = true;
            $quiz->published_at = now();
            $quiz->save();
            
            DB::commit();
            
            // Clear cache
            Cache::forget('queries.queries.published.*');
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function unpublishQuiz(int $id): bool
    {
        $quiz = $this->quizRepository->findById($id);
        
        if (!$quiz) {
            return false;
        }
        
        DB::beginTransaction();
        try {
            $quiz->is_published = false;
            $quiz->save();
            
            DB::commit();
            
            // Clear cache
            Cache::forget('queries.queries.published.*');
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getQuizForAttempt(int $id, int $userId): array
    {
        $quiz = $this->quizRepository->getWithQuestions($id);
        
        if (!$quiz || !$quiz->is_published) {
            throw new \Exception('Quiz not available');
        }
        
        // Check if user has reached max attempts
        if ($quiz->max_attempts > 0) {
            $attemptsCount = $this->attemptRepository->countCompletedByUserAndQuiz($userId, $id);
            
            if ($attemptsCount >= $quiz->max_attempts) {
                throw new \Exception('Maximum attempts reached');
            }
        }
        
        $questions = $quiz->shuffle_questions 
            ? $quiz->questions->shuffle() 
            : $quiz->questions;
        
        return [
            'quiz' => QuizDTO::fromModel($quiz),
            'questions' => $questions->map(fn($q) => [
                'id' => $q->id,
                'text' => $q->question_text,
                'options' => $q->options,
                'points' => $q->points,
            ]),
        ];
    }

    public function getPopularQuizzes(int $limit = 5): array
    {
        return $this->quizRepository->getPopularQuizzes($limit);
    }
}