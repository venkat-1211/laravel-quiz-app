<?php

namespace App\Services\Interfaces;

use App\DTOs\QuestionDTO;

interface QuestionServiceInterface
{
    /**
     * Get question by ID.
     *
     * @param int $id
     * @return QuestionDTO|null
     */
    public function getQuestionById(int $id): ?QuestionDTO;

    /**
     * Get questions by quiz ID.
     *
     * @param int $quizId
     * @param bool $shuffled
     * @return array
     */
    public function getQuestionsByQuiz(int $quizId, bool $shuffled = false): array;

    /**
     * Create a new question.
     *
     * @param array $data
     * @return QuestionDTO
     */
    public function createQuestion(array $data): QuestionDTO;

    /**
     * Update an existing question.
     *
     * @param int $id
     * @param array $data
     * @return QuestionDTO
     */
    public function updateQuestion(int $id, array $data): QuestionDTO;

    /**
     * Delete a question.
     *
     * @param int $id
     * @return bool
     */
    public function deleteQuestion(int $id): bool;

    /**
     * Bulk create questions.
     *
     * @param array $questions
     * @return bool
     */
    public function bulkCreateQuestions(array $questions): bool;

    /**
     * Bulk upload questions from CSV.
     *
     * @param int $quizId
     * @param \Illuminate\Http\UploadedFile $file
     * @return array Import results
     */
    public function bulkUploadFromCsv(int $quizId, \Illuminate\Http\UploadedFile $file): array;

    /**
     * Update question order.
     *
     * @param int $quizId
     * @param array $order
     * @return bool
     */
    public function updateOrder(int $quizId, array $order): bool;

    /**
     * Get question statistics.
     *
     * @param int $questionId
     * @return array
     */
    public function getQuestionStats(int $questionId): array;

    /**
     * Validate question data.
     *
     * @param array $data
     * @return array Validation errors
     */
    public function validateQuestion(array $data): array;

    /**
     * Duplicate a question.
     *
     * @param int $id
     * @param int|null $newQuizId
     * @return QuestionDTO
     */
    public function duplicateQuestion(int $id, ?int $newQuizId = null): QuestionDTO;

    /**
     * Import questions from another quiz.
     *
     * @param int $sourceQuizId
     * @param int $targetQuizId
     * @param array $questionIds
     * @return int Number of imported questions
     */
    public function importFromQuiz(int $sourceQuizId, int $targetQuizId, array $questionIds): int;

    /**
     * Get difficult questions analysis.
     *
     * @param int $quizId
     * @return array
     */
    public function getDifficultQuestionsAnalysis(int $quizId): array;

    /**
     * Export questions to array.
     *
     * @param int $quizId
     * @return array
     */
    public function exportQuestions(int $quizId): array;

    /**
     * Validate correct answer format.
     *
     * @param string $correctAnswer
     * @param array $options
     * @return bool
     */
    public function validateCorrectAnswer(string $correctAnswer, array $options): bool;

    /**
     * Get questions by difficulty.
     *
     * @param int $quizId
     * @param string $difficulty
     * @return array
     */
    public function getQuestionsByDifficulty(int $quizId, string $difficulty): array;

    /**
     * Randomize question order.
     *
     * @param int $quizId
     * @return bool
     */
    public function randomizeOrder(int $quizId): bool;

    /**
     * Get question performance metrics.
     *
     * @param int $questionId
     * @return array
     */
    public function getPerformanceMetrics(int $questionId): array;

    /**
     * Archive old questions.
     *
     * @param int $daysUnused
     * @return int Number of archived questions
     */
    public function archiveOldQuestions(int $daysUnused = 90): int;

    /**
     * Restore archived questions.
     *
     * @param array $questionIds
     * @return int Number of restored questions
     */
    public function restoreQuestions(array $questionIds): int;

    /**
     * Search questions.
     *
     * @param string $query
     * @param int $perPage
     * @return array
     */
    public function searchQuestions(string $query, int $perPage = 15): array;

    /**
     * Get question tags/categories.
     *
     * @param int $questionId
     * @return array
     */
    public function getQuestionTags(int $questionId): array;

    /**
     * Add explanation to question.
     *
     * @param int $questionId
     * @param string $explanation
     * @return bool
     */
    public function addExplanation(int $questionId, string $explanation): bool;

    /**
     * Attach media to question.
     *
     * @param int $questionId
     * @param string $type (image|video|audio)
     * @param string $url
     * @return bool
     */
    public function attachMedia(int $questionId, string $type, string $url): bool;

    /**
     * Get similar questions.
     *
     * @param int $questionId
     * @param int $limit
     * @return array
     */
    public function getSimilarQuestions(int $questionId, int $limit = 5): array;

    /**
     * Validate question difficulty consistency.
     *
     * @param int $quizId
     * @return array
     */
    public function validateDifficultyConsistency(int $quizId): array;

    /**
     * Bulk delete questions.
     *
     * @param array $questionIds
     * @return int Number of deleted questions
     */
    public function bulkDelete(array $questionIds): int;

    /**
     * Get question usage statistics.
     *
     * @param int $questionId
     * @return array
     */
    public function getUsageStats(int $questionId): array;

    /**
     * Convert question to different format.
     *
     * @param int $questionId
     * @param string $format (text|image|video)
     * @return bool
     */
    public function convertFormat(int $questionId, string $format): bool;

    /**
     * Get questions needing review.
     *
     * @param int $quizId
     * @return array
     */
    public function getQuestionsNeedingReview(int $quizId): array;

    public function bulkDuplicate(array $questionIds, int $targetQuizId): int;

}