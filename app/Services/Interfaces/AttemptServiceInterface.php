<?php

namespace App\Services\Interfaces;

use App\DTOs\AttemptDTO;

interface AttemptServiceInterface
{
    /**
     * Start a new quiz attempt for a user.
     *
     * @param int $userId
     * @param int $quizId
     * @return AttemptDTO
     * @throws \Exception
     */
    public function startAttempt(int $userId, int $quizId): AttemptDTO;

    /**
     * Submit an answer for a question during an attempt.
     *
     * @param int $attemptId
     * @param int $questionId
     * @param string $answer
     * @param int $timeSpent
     * @return bool
     * @throws \Exception
     */
    public function submitAnswer(int $attemptId, int $questionId, string $answer, int $timeSpent): bool;

    /**
     * Complete a quiz attempt and calculate results.
     *
     * @param int $attemptId
     * @return AttemptDTO
     * @throws \Exception
     */
    public function completeAttempt(int $attemptId): AttemptDTO;

    /**
     * Mark an attempt as timed out.
     *
     * @param int $attemptId
     * @return AttemptDTO
     * @throws \Exception
     */
    public function timeoutAttempt(int $attemptId): AttemptDTO;

    /**
     * Get the results of a completed attempt.
     *
     * @param int $attemptId
     * @return AttemptDTO
     * @throws \Exception
     */
    public function getAttemptResults(int $attemptId): AttemptDTO;

    /**
     * Get all attempts for a specific user with pagination.
     *
     * @param int $userId
     * @param int $perPage
     * @return array
     */
    public function getUserAttempts(int $userId, int $perPage = 15): array;

    /**
     * Flag or unflag a question during an attempt.
     *
     * @param int $attemptId
     * @param int $questionId
     * @param bool $flag
     * @return bool
     * @throws \Exception
     */
    public function flagQuestion(int $attemptId, int $questionId, bool $flag): bool;

    /**
     * Get the current progress of an attempt.
     *
     * @param int $attemptId
     * @return array
     */
    public function getAttemptProgress(int $attemptId): array;

    /**
     * Check if user can retry a quiz.
     *
     * @param int $userId
     * @param int $quizId
     * @return bool
     */
    public function canRetry(int $userId, int $quizId): bool;

    /**
     * Get the remaining attempts for a user on a quiz.
     *
     * @param int $userId
     * @param int $quizId
     * @return int
     */
    public function getRemainingAttempts(int $userId, int $quizId): int;

    /**
     * Pause an attempt (for future resume functionality).
     *
     * @param int $attemptId
     * @return bool
     */
    public function pauseAttempt(int $attemptId): bool;

    /**
     * Resume a paused attempt.
     *
     * @param int $attemptId
     * @return AttemptDTO
     * @throws \Exception
     */
    public function resumeAttempt(int $attemptId): AttemptDTO;

    /**
     * Get statistics for a user's attempts.
     *
     * @param int $userId
     * @return array
     */
    public function getUserStats(int $userId): array;

    /**
     * Get detailed analysis of a completed attempt.
     *
     * @param int $attemptId
     * @return array
     */
    public function getDetailedAnalysis(int $attemptId): array;

    /**
     * Export attempt data for reporting.
     *
     * @param array $filters
     * @return array
     */
    public function exportAttempts(array $filters = []): array;

    /**
     * Calculate and update attempt scores.
     *
     * @param int $attemptId
     * @return bool
     */
    public function recalculateScore(int $attemptId): bool;

    /**
     * Get time spent per question for an attempt.
     *
     * @param int $attemptId
     * @return array
     */
    public function getTimeAnalysis(int $attemptId): array;

    /**
     * Get question-by-question performance.
     *
     * @param int $attemptId
     * @return array
     */
    public function getQuestionPerformance(int $attemptId): array;

    /**
     * Check if an attempt is still valid (not expired).
     *
     * @param int $attemptId
     * @return bool
     */
    public function isValidAttempt(int $attemptId): bool;

    /**
     * Extend time for an attempt (admin feature).
     *
     * @param int $attemptId
     * @param int $extraMinutes
     * @return bool
     */
    public function extendTime(int $attemptId, int $extraMinutes): bool;

    /**
     * Review flagged questions for an attempt.
     *
     * @param int $attemptId
     * @return array
     */
    public function getFlaggedQuestions(int $attemptId): array;

    /**
     * Get the next unanswered question for an attempt.
     *
     * @param int $attemptId
     * @return array|null
     */
    public function getNextUnansweredQuestion(int $attemptId): ?array;

    /**
     * Validate if all questions in an attempt have been answered.
     *
     * @param int $attemptId
     * @return bool
     */
    public function isComplete(int $attemptId): bool;

    /**
     * Get the final score breakdown for an attempt.
     *
     * @param int $attemptId
     * @return array
     */
    public function getScoreBreakdown(int $attemptId): array;

    /**
     * Compare attempt with average performance.
     *
     * @param int $attemptId
     * @return array
     */
    public function compareWithAverage(int $attemptId): array;

    /**
     * Get recommendations based on attempt performance.
     *
     * @param int $attemptId
     * @return array
     */
    public function getRecommendations(int $attemptId): array;

    /**
     * Archive old attempts (admin feature).
     *
     * @param int $daysOld
     * @return int Number of archived attempts
     */
    public function archiveOldAttempts(int $daysOld = 30): int;

    /**
     * Restore an archived attempt.
     *
     * @param int $attemptId
     * @return bool
     */
    public function restoreAttempt(int $attemptId): bool;

    /**
     * Get attempt history with filters.
     *
     * @param array $filters
     * @param int $perPage
     * @return array
     */
    public function getAttemptHistory(array $filters = [], int $perPage = 15): array;

    /**
     * Generate certificate for passed attempts.
     *
     * @param int $attemptId
     * @return string|null URL to generated certificate
     */
    public function generateCertificate(int $attemptId): ?string;

    /**
     * Share attempt result on social media.
     *
     * @param int $attemptId
     * @param string $platform
     * @return string Shareable URL
     */
    public function shareResult(int $attemptId, string $platform = 'facebook'): string;

    /**
     * Get attempt statistics for admin dashboard.
     *
     * @param array $dateRange
     * @return array
     */
    public function getAdminStats(array $dateRange = []): array;

    /**
     * Bulk delete attempts (admin feature).
     *
     * @param array $attemptIds
     * @return int Number of deleted attempts
     */
    public function bulkDelete(array $attemptIds): int;

    /**
     * Export single attempt as PDF.
     *
     * @param int $attemptId
     * @return string Path to generated PDF
     */
    public function exportAsPdf(int $attemptId): string;

    /**
     * Get trending questions (most missed/correct).
     *
     * @param int $quizId
     * @param string $type (missed|correct)
     * @return array
     */
    public function getTrendingQuestions(int $quizId, string $type = 'missed'): array;

    /**
     * Calculate percentile rank for an attempt.
     *
     * @param int $attemptId
     * @return float
     */
    public function calculatePercentile(int $attemptId): float;

    /**
     * Get improvement suggestions based on attempt.
     *
     * @param int $attemptId
     * @return array
     */
    public function getImprovementSuggestions(int $attemptId): array;

    /**
     * Validate attempt integrity (check for anomalies).
     *
     * @param int $attemptId
     * @return array Validation results
     */
    public function validateIntegrity(int $attemptId): array;
}