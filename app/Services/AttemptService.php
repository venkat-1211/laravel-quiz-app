<?php

namespace App\Services;

use App\DTOs\AttemptDTO;
use App\Models\Attempt;
use App\Services\Interfaces\AttemptServiceInterface;
use App\Repositories\Interfaces\AttemptRepositoryInterface;
use App\Repositories\Interfaces\QuestionRepositoryInterface;
use App\Repositories\Interfaces\QuizRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AttemptService implements AttemptServiceInterface
{
    public function __construct(
        private AttemptRepositoryInterface $attemptRepository,
        private QuestionRepositoryInterface $questionRepository,
        private QuizRepositoryInterface $quizRepository
    ) {}

    /**
     * Start a new quiz attempt for a user.
     *
     * @param int $userId
     * @param int $quizId
     * @return AttemptDTO
     * @throws \Exception
     */
    public function startAttempt(int $userId, int $quizId): AttemptDTO
    {
        DB::beginTransaction();
        
        try {
            // Check for existing in-progress attempt
            $existingAttempt = $this->attemptRepository->findInProgress($userId, $quizId);
            
            if ($existingAttempt) {
                return AttemptDTO::fromModel($existingAttempt);
            }
            
            // Check if user has reached max attempts
            if (!$this->canRetry($userId, $quizId)) {
                throw new \Exception('Maximum attempts reached for this quiz.');
            }
            
            $quiz = $this->quizRepository->findById($quizId);
            $questions = $this->questionRepository->getByQuiz($quizId, $quiz->shuffle_questions ?? false);
            
            $data = [
                'user_id' => $userId,
                'quiz_id' => $quizId,
                'total_questions' => count($questions),
                'status' => 'in_progress',
                'started_at' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ];
            
            $attempt = $this->attemptRepository->create($data);
            
            // Create attempt answers for each question
            foreach ($questions as $question) {
                $attempt->answers()->create([
                    'question_id' => $question['id'],
                    'time_spent' => 0,
                    'is_flagged' => false,
                ]);
            }
            
            DB::commit();
            
            return AttemptDTO::fromModel($attempt->load('answers', 'quiz'));
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to start attempt: ' . $e->getMessage());
            throw new \Exception('Failed to start quiz attempt: ' . $e->getMessage());
        }
    }

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
    public function submitAnswer(int $attemptId, int $questionId, string $answer, int $timeSpent): bool
    {
        DB::beginTransaction();
        
        try {
            $attempt = $this->attemptRepository->findById($attemptId);
            
            if (!$attempt || $attempt->status !== 'in_progress') {
                throw new \Exception('Invalid attempt or attempt is not in progress.');
            }
            
            if (!$this->isValidAttempt($attemptId)) {
                throw new \Exception('Attempt has expired.');
            }
            
            $question = $this->questionRepository->findById($questionId);
            
            if (!$question) {
                throw new \Exception('Question not found.');
            }
            
            $isCorrect = $question->isCorrect($answer);
            
            $attemptAnswer = $attempt->answers()
                ->where('question_id', $questionId)
                ->first();
            
            if ($attemptAnswer) {
                $attemptAnswer->update([
                    'selected_answer' => $answer,
                    'is_correct' => $isCorrect,
                    'time_spent' => $timeSpent,
                ]);
            }
            
            // Recalculate score after each answer
            $attempt->calculateScore();
            
            DB::commit();
            
            Cache::forget("attempt.progress.{$attemptId}");
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to submit answer: ' . $e->getMessage());
            throw new \Exception('Failed to submit answer: ' . $e->getMessage());
        }
    }

    /**
     * Complete a quiz attempt and calculate results.
     *
     * @param int $attemptId
     * @return AttemptDTO
     * @throws \Exception
     */
    public function completeAttempt(int $attemptId): AttemptDTO
    {
        DB::beginTransaction();
        
        try {
            $attempt = $this->attemptRepository->findWithDetails($attemptId);
            
            if (!$attempt || $attempt->status !== 'in_progress') {
                throw new \Exception('Invalid attempt or attempt is already completed.');
            }
            
            // Calculate final scores
            $attempt->calculateScore();
            
            // Calculate time taken properly
            $timeTaken = now()->diffInSeconds($attempt->started_at);
            
            // Mark as completed
            $attempt->status = 'completed';
            $attempt->completed_at = now();
            $attempt->time_taken = $timeTaken; // Use calculated time
            $attempt->save();
            
            // Update leaderboard
            $this->updateLeaderboard($attempt->user_id);
            
            // Check achievements
            $this->checkAchievements($attempt->user_id);
            
            DB::commit();
            
            Cache::forget("attempt.progress.{$attemptId}");
            Cache::forget("user.attempts.{$attempt->user_id}");
            
            return AttemptDTO::fromModel($attempt->load('quiz', 'answers.question'));
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to complete attempt: ' . $e->getMessage());
            throw new \Exception('Failed to complete quiz attempt: ' . $e->getMessage());
        }
    }

    /**
     * Mark an attempt as timed out.
     *
     * @param int $attemptId
     * @return AttemptDTO
     * @throws \Exception
     */
    public function timeoutAttempt(int $attemptId): AttemptDTO
    {
        DB::beginTransaction();
        
        try {
            $attempt = $this->attemptRepository->findById($attemptId);
            
            if (!$attempt || $attempt->status !== 'in_progress') {
                throw new \Exception('Invalid attempt or attempt is already completed.');
            }
            
            $attempt->status = 'timed_out';
            $attempt->completed_at = now();
            $attempt->time_taken = now()->diffInSeconds($attempt->started_at);
            $attempt->save();
            
            // Calculate score for timed out attempt
            $attempt->calculateScore();
            
            DB::commit();
            
            // Clear cache
            Cache::forget("attempt.progress.{$attemptId}");
            
            return AttemptDTO::fromModel($attempt->load('quiz'));
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to timeout attempt: ' . $e->getMessage());
            throw new \Exception('Failed to timeout attempt: ' . $e->getMessage());
        }
    }

    /**
     * Get the results of a completed attempt.
     *
     * @param int $attemptId
     * @return AttemptDTO
     * @throws \Exception
     */
    public function getAttemptResults(int $attemptId): AttemptDTO
    {
        $attempt = Cache::remember("attempt.results.{$attemptId}", 3600, function () use ($attemptId) {
            return $this->attemptRepository->findWithDetails($attemptId);
        });
        
        if (!$attempt) {
            throw new \Exception('Attempt not found.');
        }
        
        return AttemptDTO::fromModel($attempt);
    }

    /**
     * Get all attempts for a specific user with pagination.
     *
     * @param int $userId
     * @param int $perPage
     * @return array
     */
    public function getUserAttempts(int $userId, int $perPage = 15): array
    {
        return Cache::remember("user.attempts.{$userId}.page." . request('page', 1), 300, function () use ($userId, $perPage) {
            $attempts = $this->attemptRepository->getByUser($userId, $perPage);
            
            return [
                'data' => $attempts->items(),
                'total' => $attempts->total(),
                'per_page' => $attempts->perPage(),
                'current_page' => $attempts->currentPage(),
                'last_page' => $attempts->lastPage(),
            ];
        });
    }

    /**
     * Flag or unflag a question during an attempt.
     *
     * @param int $attemptId
     * @param int $questionId
     * @param bool $flag
     * @return bool
     * @throws \Exception
     */
    public function flagQuestion(int $attemptId, int $questionId, bool $flag): bool
    {
        try {
            $attempt = $this->attemptRepository->findById($attemptId);
            
            if (!$attempt || $attempt->status !== 'in_progress') {
                throw new \Exception('Invalid attempt or attempt is not in progress.');
            }
            
            $updated = $attempt->answers()
                ->where('question_id', $questionId)
                ->update(['is_flagged' => $flag]);
            
            if ($updated) {
                Cache::forget("attempt.progress.{$attemptId}");
            }
            
            return (bool) $updated;
            
        } catch (\Exception $e) {
            Log::error('Failed to flag question: ' . $e->getMessage());
            throw new \Exception('Failed to flag question: ' . $e->getMessage());
        }
    }

    /**
     * Get the current progress of an attempt.
     *
     * @param int $attemptId
     * @return array
     */
    public function getAttemptProgress(int $attemptId): array
    {
        return Cache::remember("attempt.progress.{$attemptId}", 60, function () use ($attemptId) {
            $attempt = $this->attemptRepository->findWithDetails($attemptId);
            
            if (!$attempt) {
                return [];
            }
            
            $totalQuestions = $attempt->total_questions;
            $answeredQuestions = $attempt->answers()
                ->whereNotNull('selected_answer')
                ->count();
            
            $flaggedQuestions = $attempt->answers()
                ->where('is_flagged', true)
                ->count();
            
            $correctAnswers = $attempt->answers()
                ->where('is_correct', true)
                ->count();
            
            $incorrectAnswers = $attempt->answers()
                ->where('is_correct', false)
                ->whereNotNull('selected_answer')
                ->count();
            
            $skippedQuestions = $attempt->answers()
                ->whereNull('selected_answer')
                ->count();
            
            $progress = $totalQuestions > 0 ? ($answeredQuestions / $totalQuestions) * 100 : 0;
            
            // Calculate time remaining if quiz has time limit
            $timeRemaining = null;
            $timePercentage = 100;
            
            if ($attempt->quiz && $attempt->quiz->time_limit > 0) {
                $timeLimitInSeconds = $attempt->quiz->time_limit * 60;
                $timeElapsed = now()->diffInSeconds($attempt->started_at);
                $timeRemaining = max(0, $timeLimitInSeconds - $timeElapsed);
                $timePercentage = $timeLimitInSeconds > 0 
                    ? max(0, ($timeRemaining / $timeLimitInSeconds) * 100) 
                    : 100;
            }
            
            return [
                'attempt_id' => $attemptId,
                'status' => $attempt->status,
                'total_questions' => $totalQuestions,
                'answered_questions' => $answeredQuestions,
                'remaining_questions' => $totalQuestions - $answeredQuestions,
                'flagged_questions' => $flaggedQuestions,
                'correct_answers' => $correctAnswers,
                'incorrect_answers' => $incorrectAnswers,
                'skipped_questions' => $skippedQuestions,
                'progress_percentage' => round($progress, 2),
                'time_remaining' => $timeRemaining,
                'time_remaining_formatted' => $timeRemaining ? gmdate('H:i:s', $timeRemaining) : null,
                'time_percentage' => round($timePercentage, 2),
                'started_at' => $attempt->started_at->toDateTimeString(),
                'elapsed_time' => now()->diffInSeconds($attempt->started_at),
            ];
        });
    }

    /**
     * Check if user can retry a quiz.
     *
     * @param int $userId
     * @param int $quizId
     * @return bool
     */
    public function canRetry(int $userId, int $quizId): bool
    {
        $remainingAttempts = $this->getRemainingAttempts($userId, $quizId);
        return $remainingAttempts > 0;
    }

    /**
     * Get the remaining attempts for a user on a quiz.
     *
     * @param int $userId
     * @param int $quizId
     * @return int
     */
    public function getRemainingAttempts(int $userId, int $quizId): int
    {
        $quiz = $this->quizRepository->findById($quizId);
        
        if (!$quiz) {
            return 0;
        }
        
        // If max_attempts is 0, unlimited attempts
        if ($quiz->max_attempts === 0) {
            return PHP_INT_MAX;
        }
        
        $completedAttempts = Attempt::where('user_id', $userId)
            ->where('quiz_id', $quizId)
            ->whereIn('status', ['completed', 'timed_out'])
            ->count();
        
        return max(0, $quiz->max_attempts - $completedAttempts);
    }

    /**
     * Pause an attempt (for future resume functionality).
     *
     * @param int $attemptId
     * @return bool
     */
    public function pauseAttempt(int $attemptId): bool
    {
        try {
            $attempt = $this->attemptRepository->findById($attemptId);
            
            if (!$attempt || $attempt->status !== 'in_progress') {
                return false;
            }
            
            // Store current state in cache for potential resume
            Cache::put("attempt.paused.{$attemptId}", [
                'paused_at' => now(),
                'progress' => $this->getAttemptProgress($attemptId),
            ], now()->addHours(24));
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to pause attempt: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Resume a paused attempt.
     *
     * @param int $attemptId
     * @return AttemptDTO
     * @throws \Exception
     */
    public function resumeAttempt(int $attemptId): AttemptDTO
    {
        $attempt = $this->attemptRepository->findById($attemptId);
        
        if (!$attempt) {
            throw new \Exception('Attempt not found.');
        }
        
        if ($attempt->status !== 'in_progress') {
            // Check if it was paused
            $pausedData = Cache::get("attempt.paused.{$attemptId}");
            
            if (!$pausedData) {
                throw new \Exception('Cannot resume attempt that is not in progress.');
            }
            
            // Restore to in_progress
            $attempt->status = 'in_progress';
            $attempt->save();
            
            Cache::forget("attempt.paused.{$attemptId}");
        }
        
        return AttemptDTO::fromModel($attempt->load('answers', 'quiz'));
    }

    /**
     * Get statistics for a user's attempts.
     *
     * @param int $userId
     * @return array
     */
    public function getUserStats(int $userId): array
    {
        return Cache::remember("user.stats.{$userId}", 3600, function () use ($userId) {
            $stats = Attempt::where('user_id', $userId)
                ->where('status', 'completed')
                ->select(
                    DB::raw('COUNT(*) as total_attempts'),
                    DB::raw('AVG(percentage_score) as average_score'),
                    DB::raw('SUM(score) as total_points'),
                    DB::raw('COUNT(DISTINCT quiz_id) as unique_quizzes'),
                    DB::raw('MAX(percentage_score) as highest_score'),
                    DB::raw('MIN(percentage_score) as lowest_score'),
                    DB::raw('AVG(time_taken) as average_time_taken')
                )
                ->first();
            
            // Get recent performance trend
            $trend = Attempt::where('user_id', $userId)
                ->where('status', 'completed')
                ->orderBy('completed_at', 'desc')
                ->limit(10)
                ->get()
                ->map(fn($a) => [
                    'date' => $a->completed_at->format('Y-m-d'),
                    'score' => $a->percentage_score,
                ]);
            
            // Get category performance
            $categoryPerformance = DB::table('attempts')
                ->join('quizzes', 'attempts.quiz_id', '=', 'quizzes.id')
                ->join('categories', 'quizzes.category_id', '=', 'categories.id')
                ->where('attempts.user_id', $userId)
                ->where('attempts.status', 'completed')
                ->select(
                    'categories.name',
                    DB::raw('AVG(attempts.percentage_score) as avg_score'),
                    DB::raw('COUNT(*) as attempts_count')
                )
                ->groupBy('categories.id', 'categories.name')
                ->get();
            
            return [
                'total_attempts' => (int) ($stats->total_attempts ?? 0),
                'average_score' => round($stats->average_score ?? 0, 2),
                'total_points' => (int) ($stats->total_points ?? 0),
                'unique_quizzes' => (int) ($stats->unique_quizzes ?? 0),
                'highest_score' => round($stats->highest_score ?? 0, 2),
                'lowest_score' => round($stats->lowest_score ?? 0, 2),
                'average_time_taken' => (int) ($stats->average_time_taken ?? 0),
                'performance_trend' => $trend,
                'category_performance' => $categoryPerformance,
            ];
        });
    }

    /**
     * Get detailed analysis of a completed attempt.
     *
     * @param int $attemptId
     * @return array
     */
    public function getDetailedAnalysis(int $attemptId): array
    {
        $attempt = $this->attemptRepository->findWithDetails($attemptId);
        
        if (!$attempt || $attempt->status !== 'completed') {
            return [];
        }
        
        $analysis = [
            'overall' => [
                'score' => $attempt->percentage_score,
                'correct' => $attempt->correct_answers,
                'incorrect' => $attempt->incorrect_answers,
                'skipped' => $attempt->skipped_answers,
                'total_questions' => $attempt->total_questions,
                'time_taken' => $attempt->time_taken,
                'passed' => $attempt->percentage_score >= ($attempt->quiz->passing_score ?? 70),
            ],
            'by_difficulty' => $this->analyzeByDifficulty($attempt),
            'by_category' => $this->analyzeByCategory($attempt),
            'time_analysis' => $this->getTimeAnalysis($attemptId),
            'question_performance' => $this->getQuestionPerformance($attemptId),
            'comparison' => $this->compareWithAverage($attemptId),
        ];
        
        return $analysis;
    }

    /**
     * Export attempt data for reporting.
     *
     * @param array $filters
     * @return array
     */
    public function exportAttempts(array $filters = []): array
    {
        $query = Attempt::with(['user', 'quiz'])
            ->where('status', 'completed');
        
        if (isset($filters['start_date'])) {
            $query->where('completed_at', '>=', $filters['start_date']);
        }
        
        if (isset($filters['end_date'])) {
            $query->where('completed_at', '<=', $filters['end_date']);
        }
        
        if (isset($filters['quiz_id'])) {
            $query->where('quiz_id', $filters['quiz_id']);
        }
        
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        
        if (isset($filters['min_score'])) {
            $query->where('percentage_score', '>=', $filters['min_score']);
        }
        
        $attempts = $query->orderBy('completed_at', 'desc')->get();
        
        return $attempts->map(fn($a) => [
            'attempt_id' => $a->id,
            'user_name' => $a->user->name,
            'user_email' => $a->user->email,
            'quiz_title' => $a->quiz->title,
            'quiz_category' => $a->quiz->category->name ?? 'Uncategorized',
            'score' => $a->score,
            'percentage' => $a->percentage_score,
            'correct' => $a->correct_answers,
            'incorrect' => $a->incorrect_answers,
            'skipped' => $a->skipped_answers,
            'time_taken' => $a->time_taken,
            'started_at' => $a->started_at->toDateTimeString(),
            'completed_at' => $a->completed_at->toDateTimeString(),
        ])->toArray();
    }

    /**
     * Calculate and update attempt scores.
     *
     * @param int $attemptId
     * @return bool
     */
    public function recalculateScore(int $attemptId): bool
    {
        try {
            $attempt = $this->attemptRepository->findById($attemptId);
            
            if (!$attempt) {
                return false;
            }
            
            $attempt->calculateScore();
            
            Cache::forget("attempt.results.{$attemptId}");
            Cache::forget("attempt.progress.{$attemptId}");
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to recalculate score: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get time spent per question for an attempt.
     *
     * @param int $attemptId
     * @return array
     */
    public function getTimeAnalysis(int $attemptId): array
    {
        $attempt = $this->attemptRepository->findWithDetails($attemptId);
        
        if (!$attempt) {
            return [];
        }
        
        $timeAnalysis = $attempt->answers()
            ->with('question')
            ->get()
            ->map(fn($a) => [
                'question_id' => $a->question_id,
                'question_text' => $a->question->question_text,
                'time_spent' => $a->time_spent,
                'is_correct' => $a->is_correct,
            ])
            ->toArray();
        
        $totalTime = array_sum(array_column($timeAnalysis, 'time_spent'));
        $averageTime = count($timeAnalysis) > 0 ? $totalTime / count($timeAnalysis) : 0;
        
        // Find fastest and slowest questions
        usort($timeAnalysis, fn($a, $b) => $a['time_spent'] <=> $b['time_spent']);
        
        $fastest = array_slice($timeAnalysis, 0, 3);
        $slowest = array_slice($timeAnalysis, -3, 3);
        
        return [
            'total_time' => $totalTime,
            'average_time_per_question' => round($averageTime, 2),
            'fastest_questions' => $fastest,
            'slowest_questions' => array_reverse($slowest),
            'detailed' => $timeAnalysis,
        ];
    }

    /**
     * Get question-by-question performance.
     *
     * @param int $attemptId
     * @return array
     */
    public function getQuestionPerformance(int $attemptId): array
    {
        $attempt = $this->attemptRepository->findWithDetails($attemptId);
        
        if (!$attempt) {
            return [];
        }
        
        return $attempt->answers()
            ->with('question')
            ->get()
            ->map(fn($a) => [
                'question_id' => $a->question_id,
                'question_text' => $a->question->question_text,
                'user_answer' => $a->selected_answer,
                'correct_answer' => $a->question->correct_answer,
                'is_correct' => $a->is_correct,
                'time_spent' => $a->time_spent,
                'explanation' => $a->question->explanation,
                'options' => $a->question->options,
                'difficulty' => $a->question->difficulty,
            ])
            ->toArray();
    }

    /**
     * Check if an attempt is still valid (not expired).
     *
     * @param int $attemptId
     * @return bool
     */
    public function isValidAttempt(int $attemptId): bool
    {
        $attempt = $this->attemptRepository->findById($attemptId);
        
        if (!$attempt || $attempt->status !== 'in_progress') {
            return false;
        }
        
        // Check if attempt has exceeded time limit
        if ($attempt->quiz && $attempt->quiz->time_limit > 0) {
            $timeLimitInSeconds = $attempt->quiz->time_limit * 60;
            $timeElapsed = now()->diffInSeconds($attempt->started_at);
            
            if ($timeElapsed > $timeLimitInSeconds) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Extend time for an attempt (admin feature).
     *
     * @param int $attemptId
     * @param int $extraMinutes
     * @return bool
     */
    public function extendTime(int $attemptId, int $extraMinutes): bool
    {
        // This is an admin feature - you might want to log who extended it
        try {
            $attempt = $this->attemptRepository->findById($attemptId);
            
            if (!$attempt || $attempt->status !== 'in_progress') {
                return false;
            }
            
            // You could store extended time in cache or a separate table
            Cache::put("attempt.extended_time.{$attemptId}", $extraMinutes, now()->addHours(24));
            
            Log::info('Attempt time extended', [
                'attempt_id' => $attemptId,
                'extra_minutes' => $extraMinutes,
                'extended_by' => auth()->id(),
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to extend attempt time: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Review flagged questions for an attempt.
     *
     * @param int $attemptId
     * @return array
     */
    public function getFlaggedQuestions(int $attemptId): array
    {
        $attempt = $this->attemptRepository->findById($attemptId);
        
        if (!$attempt) {
            return [];
        }
        
        return $attempt->answers()
            ->with('question')
            ->where('is_flagged', true)
            ->get()
            ->map(fn($a) => [
                'question_id' => $a->question_id,
                'question_text' => $a->question->question_text,
                'user_answer' => $a->selected_answer,
                'correct_answer' => $a->question->correct_answer,
                'is_correct' => $a->is_correct,
                'options' => $a->question->options,
            ])
            ->toArray();
    }

    /**
     * Get the next unanswered question for an attempt.
     *
     * @param int $attemptId
     * @return array|null
     */
    public function getNextUnansweredQuestion(int $attemptId): ?array
    {
        $attempt = $this->attemptRepository->findById($attemptId);
        
        if (!$attempt || $attempt->status !== 'in_progress') {
            return null;
        }
        
        $nextQuestion = $attempt->answers()
            ->with('question')
            ->whereNull('selected_answer')
            ->orderBy('id')
            ->first();
        
        if (!$nextQuestion) {
            return null;
        }
        
        return [
            'question_id' => $nextQuestion->question_id,
            'question_text' => $nextQuestion->question->question_text,
            'options' => $nextQuestion->question->options,
            'index' => $nextQuestion->id,
        ];
    }

    /**
     * Validate if all questions in an attempt have been answered.
     *
     * @param int $attemptId
     * @return bool
     */
    public function isComplete(int $attemptId): bool
    {
        $attempt = $this->attemptRepository->findById($attemptId);
        
        if (!$attempt) {
            return false;
        }
        
        $unansweredCount = $attempt->answers()
            ->whereNull('selected_answer')
            ->count();
        
        return $unansweredCount === 0;
    }

    /**
     * Get the final score breakdown for an attempt.
     *
     * @param int $attemptId
     * @return array
     */
    public function getScoreBreakdown(int $attemptId): array
    {
        $attempt = $this->attemptRepository->findWithDetails($attemptId);
        
        if (!$attempt || $attempt->status !== 'completed') {
            return [];
        }
        
        $correctAnswers = $attempt->answers()
            ->with('question')
            ->where('is_correct', true)
            ->get();
        
        $incorrectAnswers = $attempt->answers()
            ->with('question')
            ->where('is_correct', false)
            ->whereNotNull('selected_answer')
            ->get();
        
        $skippedAnswers = $attempt->answers()
            ->with('question')
            ->whereNull('selected_answer')
            ->get();
        
        $pointsByDifficulty = [
            'easy' => ['earned' => 0, 'total' => 0],
            'medium' => ['earned' => 0, 'total' => 0],
            'hard' => ['earned' => 0, 'total' => 0],
        ];
        
        foreach ($attempt->answers as $answer) {
            $difficulty = $answer->question->difficulty;
            $pointsByDifficulty[$difficulty]['total'] += $answer->question->points;
            
            if ($answer->is_correct) {
                $pointsByDifficulty[$difficulty]['earned'] += $answer->question->points;
            }
        }
        
        return [
            'total_score' => $attempt->score,
            'max_possible' => $attempt->answers->sum(fn($a) => $a->question->points),
            'correct_count' => $correctAnswers->count(),
            'incorrect_count' => $incorrectAnswers->count(),
            'skipped_count' => $skippedAnswers->count(),
            'points_by_difficulty' => $pointsByDifficulty,
            'percentage' => $attempt->percentage_score,
        ];
    }

    /**
     * Compare attempt with average performance.
     *
     * @param int $attemptId
     * @return array
     */
    public function compareWithAverage(int $attemptId): array
    {
        $attempt = $this->attemptRepository->findWithDetails($attemptId);
        
        if (!$attempt || $attempt->status !== 'completed') {
            return [];
        }
        
        // Get average for this quiz
        $quizAverage = Attempt::where('quiz_id', $attempt->quiz_id)
            ->where('status', 'completed')
            ->avg('percentage_score');
        
        // Get average for all users
        $globalAverage = Attempt::where('status', 'completed')
            ->avg('percentage_score');
        
        // Get percentile
        $betterThanCount = Attempt::where('quiz_id', $attempt->quiz_id)
            ->where('status', 'completed')
            ->where('percentage_score', '<', $attempt->percentage_score)
            ->count();
        
        $totalAttempts = Attempt::where('quiz_id', $attempt->quiz_id)
            ->where('status', 'completed')
            ->count();
        
        $percentile = $totalAttempts > 0 
            ? round(($betterThanCount / $totalAttempts) * 100, 2)
            : 0;
        
        return [
            'user_score' => $attempt->percentage_score,
            'quiz_average' => round($quizAverage ?? 0, 2),
            'global_average' => round($globalAverage ?? 0, 2),
            'difference_from_quiz_average' => round($attempt->percentage_score - ($quizAverage ?? 0), 2),
            'difference_from_global_average' => round($attempt->percentage_score - ($globalAverage ?? 0), 2),
            'percentile' => $percentile,
            'rank' => $betterThanCount + 1,
            'total_participants' => $totalAttempts,
        ];
    }

    /**
     * Get recommendations based on attempt performance.
     *
     * @param int $attemptId
     * @return array
     */
    public function getRecommendations(int $attemptId): array
    {
        $attempt = $this->attemptRepository->findWithDetails($attemptId);
        
        if (!$attempt || $attempt->status !== 'completed') {
            return [];
        }
        
        $recommendations = [];
        
        // Find weak areas (incorrect answers)
        $incorrectQuestions = $attempt->answers()
            ->with('question')
            ->where('is_correct', false)
            ->whereNotNull('selected_answer')
            ->get();
        
        if ($incorrectQuestions->isNotEmpty()) {
            $topics = $incorrectQuestions->groupBy(fn($a) => $a->question->difficulty);
            
            foreach ($topics as $difficulty => $questions) {
                $recommendations[] = [
                    'type' => 'weak_area',
                    'difficulty' => $difficulty,
                    'count' => $questions->count(),
                    'message' => "You struggled with {$questions->count()} {$difficulty} questions. Consider practicing more {$difficulty} level quizzes.",
                ];
            }
        }
        
        // Time management recommendations
        $timeAnalysis = $this->getTimeAnalysis($attemptId);
        if ($timeAnalysis['average_time_per_question'] > 60) {
            $recommendations[] = [
                'type' => 'time_management',
                'message' => 'You spent an average of ' . round($timeAnalysis['average_time_per_question']) . ' seconds per question. Try to improve your speed.',
            ];
        }
        
        // Skipped questions
        if ($attempt->skipped_answers > 0) {
            $recommendations[] = [
                'type' => 'skipped',
                'count' => $attempt->skipped_answers,
                'message' => "You skipped {$attempt->skipped_answers} questions. Try to attempt all questions even if unsure.",
            ];
        }
        
        // Suggest similar quizzes
        $similarQuizzes = $this->quizRepository->getSimilarDifficultyQuizzes(
            $attempt->user_id,
            $attempt->quiz->difficulty,
            3
        );
        
        if ($similarQuizzes->isNotEmpty()) {
            $recommendations[] = [
                'type' => 'similar_quizzes',
                'quizzes' => $similarQuizzes->map(fn($q) => [
                    'id' => $q->id,
                    'title' => $q->title,
                    'difficulty' => $q->difficulty,
                ])->toArray(),
                'message' => 'Based on your performance, you might enjoy these similar quizzes:',
            ];
        }
        
        return $recommendations;
    }

    /**
     * Archive old attempts (admin feature).
     *
     * @param int $daysOld
     * @return int Number of archived attempts
     */
    public function archiveOldAttempts(int $daysOld = 30): int
    {
        $cutoffDate = now()->subDays($daysOld);
        
        $attempts = Attempt::where('status', 'completed')
            ->where('completed_at', '<', $cutoffDate)
            ->get();
        
        if ($attempts->isEmpty()) {
            return 0;
        }
        
        DB::beginTransaction();
        
        try {
            foreach ($attempts as $attempt) {
                // Move to archive table (create attempts_archive table first)
                DB::table('attempts_archive')->insert([
                    'original_id' => $attempt->id,
                    'user_id' => $attempt->user_id,
                    'quiz_id' => $attempt->quiz_id,
                    'score' => $attempt->score,
                    'percentage_score' => $attempt->percentage_score,
                    'data' => json_encode($attempt->toArray()),
                    'archived_at' => now(),
                ]);
                
                // Optionally delete the original
                // $attempt->delete();
            }
            
            DB::commit();
            
            return $attempts->count();
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to archive old attempts: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Restore an archived attempt.
     *
     * @param int $attemptId
     * @return bool
     */
    public function restoreAttempt(int $attemptId): bool
    {
        // This would restore from attempts_archive table
        // Implementation depends on your archive strategy
        return false;
    }

    /**
     * Get attempt history with filters.
     *
     * @param array $filters
     * @param int $perPage
     * @return array
     */
    public function getAttemptHistory(array $filters = [], int $perPage = 15): array
    {
        $query = Attempt::with(['user', 'quiz'])
            ->orderBy('created_at', 'desc');
        
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        
        if (isset($filters['quiz_id'])) {
            $query->where('quiz_id', $filters['quiz_id']);
        }
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }
        
        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }
        
        if (isset($filters['min_score'])) {
            $query->where('percentage_score', '>=', $filters['min_score']);
        }
        
        $attempts = $query->paginate($perPage);
        
        return [
            'data' => $attempts->items(),
            'total' => $attempts->total(),
            'per_page' => $attempts->perPage(),
            'current_page' => $attempts->currentPage(),
            'last_page' => $attempts->lastPage(),
        ];
    }

    /**
     * Generate certificate for passed attempts.
     *
     * @param int $attemptId
     * @return string|null URL to generated certificate
     */
    public function generateCertificate(int $attemptId): ?string
    {
        $attempt = $this->attemptRepository->findWithDetails($attemptId);
        
        if (!$attempt || $attempt->status !== 'completed') {
            return null;
        }
        
        // Check if user passed
        if ($attempt->percentage_score < ($attempt->quiz->passing_score ?? 70)) {
            return null;
        }
        
        // Generate certificate logic here
        // This would typically create a PDF and store it
        // Return the URL to the certificate
        
        return route('certificate.download', $attemptId);
    }

    /**
     * Share attempt result on social media.
     *
     * @param int $attemptId
     * @param string $platform
     * @return string Shareable URL
     */
    public function shareResult(int $attemptId, string $platform = 'facebook'): string
    {
        $attempt = $this->attemptRepository->findWithDetails($attemptId);
        
        if (!$attempt || $attempt->status !== 'completed') {
            return '';
        }
        
        $message = urlencode("I scored {$attempt->percentage_score}% on '{$attempt->quiz->title}'! Can you beat my score?");
        $url = urlencode(route('quizzes.show', $attempt->quiz->slug));
        
        switch ($platform) {
            case 'twitter':
                return "https://twitter.com/intent/tweet?text={$message}&url={$url}";
            case 'facebook':
                return "https://www.facebook.com/sharer/sharer.php?u={$url}&quote={$message}";
            case 'linkedin':
                return "https://www.linkedin.com/sharing/share-offsite/?url={$url}";
            default:
                return '';
        }
    }

    /**
     * Get attempt statistics for admin dashboard.
     *
     * @param array $dateRange
     * @return array
     */
    public function getAdminStats(array $dateRange = []): array
    {
        $query = Attempt::query();
        
        if (isset($dateRange['start'])) {
            $query->where('created_at', '>=', $dateRange['start']);
        }
        
        if (isset($dateRange['end'])) {
            $query->where('created_at', '<=', $dateRange['end']);
        }
        
        $totalAttempts = $query->count();
        $completedAttempts = (clone $query)->where('status', 'completed')->count();
        $inProgressAttempts = (clone $query)->where('status', 'in_progress')->count();
        $timedOutAttempts = (clone $query)->where('status', 'timed_out')->count();
        
        $averageScore = (clone $query)->where('status', 'completed')->avg('percentage_score');
        
        $attemptsPerDay = (clone $query)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();
        
        return [
            'total_attempts' => $totalAttempts,
            'completed_attempts' => $completedAttempts,
            'in_progress_attempts' => $inProgressAttempts,
            'timed_out_attempts' => $timedOutAttempts,
            'completion_rate' => $totalAttempts > 0 ? round(($completedAttempts / $totalAttempts) * 100, 2) : 0,
            'average_score' => round($averageScore ?? 0, 2),
            'attempts_per_day' => $attemptsPerDay,
        ];
    }

    /**
     * Bulk delete attempts (admin feature).
     *
     * @param array $attemptIds
     * @return int Number of deleted attempts
     */
    public function bulkDelete(array $attemptIds): int
    {
        if (empty($attemptIds)) {
            return 0;
        }
        
        DB::beginTransaction();
        
        try {
            // Delete related answers first
            DB::table('attempt_answers')
                ->whereIn('attempt_id', $attemptIds)
                ->delete();
            
            // Delete attempts
            $deleted = DB::table('attempts')
                ->whereIn('id', $attemptIds)
                ->delete();
            
            DB::commit();
            
            // Clear cache
            foreach ($attemptIds as $id) {
                Cache::forget("attempt.results.{$id}");
                Cache::forget("attempt.progress.{$id}");
            }
            
            return $deleted;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to bulk delete attempts: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Export single attempt as PDF.
     *
     * @param int $attemptId
     * @return string Path to generated PDF
     */
    public function exportAsPdf(int $attemptId): string
    {
        // This would generate a PDF using a library like DomPDF
        // Return the file path
        return '';
    }

    /**
     * Get trending questions (most missed/correct).
     *
     * @param int $quizId
     * @param string $type (missed|correct)
     * @return array
     */
    public function getTrendingQuestions(int $quizId, string $type = 'missed'): array
    {
        $query = DB::table('attempt_answers')
            ->join('questions', 'attempt_answers.question_id', '=', 'questions.id')
            ->join('attempts', 'attempt_answers.attempt_id', '=', 'attempts.id')
            ->where('questions.quiz_id', $quizId)
            ->where('attempts.status', 'completed');
        
        if ($type === 'missed') {
            $query->where('attempt_answers.is_correct', false)
                ->whereNotNull('selected_answer');
        } else {
            $query->where('attempt_answers.is_correct', true);
        }
        
        return $query->select(
                'questions.id',
                'questions.question_text',
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('questions.id', 'questions.question_text')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Calculate percentile rank for an attempt.
     *
     * @param int $attemptId
     * @return float
     */
    public function calculatePercentile(int $attemptId): float
    {
        $attempt = $this->attemptRepository->findById($attemptId);
        
        if (!$attempt || $attempt->status !== 'completed') {
            return 0;
        }
        
        $betterThanCount = Attempt::where('quiz_id', $attempt->quiz_id)
            ->where('status', 'completed')
            ->where('percentage_score', '<', $attempt->percentage_score)
            ->count();
        
        $totalAttempts = Attempt::where('quiz_id', $attempt->quiz_id)
            ->where('status', 'completed')
            ->count();
        
        if ($totalAttempts === 0) {
            return 100;
        }
        
        return round(($betterThanCount / $totalAttempts) * 100, 2);
    }

    /**
     * Get improvement suggestions based on attempt.
     *
     * @param int $attemptId
     * @return array
     */
    public function getImprovementSuggestions(int $attemptId): array
    {
        // Reuse recommendations method
        return $this->getRecommendations($attemptId);
    }

    /**
     * Validate attempt integrity (check for anomalies).
     *
     * @param int $attemptId
     * @return array Validation results
     */
    public function validateIntegrity(int $attemptId): array
    {
        $attempt = $this->attemptRepository->findWithDetails($attemptId);
        
        if (!$attempt) {
            return ['valid' => false, 'errors' => ['Attempt not found']];
        }
        
        $errors = [];
        $warnings = [];
        
        // Check if all questions have answers
        $unansweredCount = $attempt->answers()->whereNull('selected_answer')->count();
        if ($unansweredCount > 0 && $attempt->status === 'completed') {
            $warnings[] = "Attempt marked as completed but has {$unansweredCount} unanswered questions";
        }
        
        // Check total questions count
        if ($attempt->answers()->count() != $attempt->total_questions) {
            $errors[] = "Question count mismatch: Answers count does not match total questions";
        }
        
        // Check score calculation
        $correctCount = $attempt->answers()->where('is_correct', true)->count();
        $calculatedScore = $correctCount * ($attempt->quiz->points_per_question ?? 10);
        
        if (abs($calculatedScore - $attempt->score) > 0.01) {
            $warnings[] = "Score calculation may be incorrect: Stored: {$attempt->score}, Calculated: {$calculatedScore}";
        }
        
        // Check time consistency
        if ($attempt->status === 'completed' && $attempt->completed_at) {
            $actualTimeTaken = $attempt->completed_at->diffInSeconds($attempt->started_at);
            if (abs($actualTimeTaken - $attempt->time_taken) > 5) { // Allow 5 seconds difference
                $warnings[] = "Time taken may be incorrect: Stored: {$attempt->time_taken}s, Actual: {$actualTimeTaken}s";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Analyze performance by difficulty level.
     *
     * @param Attempt $attempt
     * @return array
     */
    private function analyzeByDifficulty(Attempt $attempt): array
    {
        $analysis = [];
        
        foreach (['easy', 'medium', 'hard'] as $difficulty) {
            $questions = $attempt->answers()
                ->whereHas('question', fn($q) => $q->where('difficulty', $difficulty))
                ->get();
            
            $total = $questions->count();
            $correct = $questions->where('is_correct', true)->count();
            
            $analysis[$difficulty] = [
                'total' => $total,
                'correct' => $correct,
                'incorrect' => $total - $correct,
                'percentage' => $total > 0 ? round(($correct / $total) * 100, 2) : 0,
            ];
        }
        
        return $analysis;
    }

    /**
     * Analyze performance by category.
     *
     * @param Attempt $attempt
     * @return array
     */
    private function analyzeByCategory(Attempt $attempt): array
    {
        // This would require category relationship through questions
        // For now, return empty array
        return [];
    }

    /**
     * Update leaderboard for user.
     *
     * @param int $userId
     * @return void
     */
    private function updateLeaderboard(int $userId): void
    {
        // This would be implemented by LeaderboardService
        // For now, we'll just log it
        Log::info("Leaderboard update needed for user: {$userId}");
    }

    /**
     * Check and award achievements for user.
     *
     * @param int $userId
     * @return void
     */
    private function checkAchievements(int $userId): void
    {
        // This would be implemented by AchievementService
        // For now, we'll just log it
        Log::info("Achievement check needed for user: {$userId}");
    }
}