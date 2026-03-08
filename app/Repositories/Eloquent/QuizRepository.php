<?php

namespace App\Repositories\Eloquent;

use App\Models\Quiz;
use App\Repositories\Interfaces\QuizRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class QuizRepository implements QuizRepositoryInterface
{
    public function findById(int $id): ?Quiz
    {
        return Cache::remember("quiz.id.{$id}", 3600, function () use ($id) {
            return Quiz::with(['category', 'questions'])->find($id);
        });
    }

    public function findBySlug(string $slug): ?Quiz
    {
        return Cache::remember("quiz.slug.{$slug}", 3600, function () use ($slug) {
            return Quiz::with(['category', 'questions'])->where('slug', $slug)->first();
        });
    }

    public function create(array $data): Quiz
    {
        $quiz = Quiz::create($data);
        $this->clearCache();
        return $quiz;
    }

    public function update(int $id, array $data): Quiz
    {
        $quiz = $this->findById($id);
        if (!$quiz) {
            throw new \Exception("Quiz not found");
        }
        $quiz->update($data);
        $this->clearCache($id, $quiz->slug);
        return $quiz;
    }

    public function delete(int $id): bool
    {
        $quiz = $this->findById($id);
        if (!$quiz) {
            return false;
        }
        $result = $quiz->delete();
        $this->clearCache($id, $quiz->slug);
        return $result;
    }

    public function getAllPublished(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $cacheKey = 'queries.quizzes.published.' . md5(serialize($filters) . $perPage . request('page', 1));
        
        return Cache::remember($cacheKey, 3600, function () use ($filters, $perPage) {
            $query = Quiz::with(['category'])->published();
            
            if (!empty($filters['search'])) {
                $query->search($filters['search']);
            }
            
            if (!empty($filters['category'])) {
                $query->byCategory($filters['category']);
            }
            
            if (!empty($filters['difficulty'])) {
                $query->byDifficulty($filters['difficulty']);
            }
            
            return $query->orderBy('published_at', 'desc')->paginate($perPage);
        });
    }

    public function getWithQuestions(int $id): ?Quiz
    {
        return Cache::remember("quiz.with_questions.{$id}", 3600, function () use ($id) {
            return Quiz::with(['questions' => function($query) {
                $query->orderBy('order');
            }])->find($id);
        });
    }

    public function getPopularQuizzes(int $limit = 5): array
    {
        return Cache::remember("quiz.popular.{$limit}", 3600, function () use ($limit) {
            return Quiz::published()
                ->withCount('attempts')
                ->orderBy('attempts_count', 'desc')
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }

    public function getQuizzesByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        return Cache::remember("quiz.category.{$categoryId}.page." . request('page', 1), 3600, function () use ($categoryId, $perPage) {
            return Quiz::with(['category', 'questions'])
                ->published()
                ->byCategory($categoryId)
                ->orderBy('published_at', 'desc')
                ->paginate($perPage);
        });
    }

    public function updateTotalQuestions(int $quizId): void
    {
        $quiz = $this->findById($quizId);
        if ($quiz) {
            $quiz->updateTotalQuestions();
            $this->clearCache($quizId, $quiz->slug);
        }
    }

    public function getAllForAdmin(int $perPage = 15): LengthAwarePaginator
    {
        return Quiz::with(['category'])
            ->withCount('questions')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return Quiz::where('title', 'LIKE', "%{$query}%")
            ->orWhere('description', 'LIKE', "%{$query}%")
            ->with(['category'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getByDifficulty(string $difficulty, int $perPage = 15): LengthAwarePaginator
    {
        return Quiz::with(['category'])
            ->where('difficulty', $difficulty)
            ->where('is_published', true)
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }

    public function getRecentQuizzes(int $limit = 10): Collection
    {
        return Quiz::with(['category'])
            ->where('is_published', true)
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getFeaturedQuizzes(int $limit = 6): Collection
    {
        return Quiz::with(['category'])
            ->where('is_published', true)
            ->where('featured_image', '!=', null)
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public function getQuizStats(int $quizId): array
    {
        return Cache::remember("quiz.stats.{$quizId}", 3600, function () use ($quizId) {
            $totalAttempts = DB::table('attempts')->where('quiz_id', $quizId)->count();
            $completedAttempts = DB::table('attempts')->where('quiz_id', $quizId)->where('status', 'completed')->count();
            $avgScore = DB::table('attempts')->where('quiz_id', $quizId)->where('status', 'completed')->avg('percentage_score');
            $totalUsers = DB::table('attempts')->where('quiz_id', $quizId)->distinct('user_id')->count('user_id');
            
            return [
                'total_attempts' => $totalAttempts,
                'completed_attempts' => $completedAttempts,
                'completion_rate' => $totalAttempts > 0 ? round(($completedAttempts / $totalAttempts) * 100, 2) : 0,
                'average_score' => round($avgScore ?? 0, 2),
                'total_users' => $totalUsers,
            ];
        });
    }

    public function getLowAttemptQuizzes(int $threshold = 10, int $limit = 20): Collection
    {
        return Quiz::withCount('attempts')
            ->having('attempts_count', '<', $threshold)
            ->orderBy('attempts_count')
            ->limit($limit)
            ->get();
    }

    public function getQuizzesNeedingReview(): Collection
    {
        return Quiz::where('is_published', false)
            ->orWhereDoesntHave('questions')
            ->orWhere('total_questions', 0)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function bulkUpdateStatus(array $quizIds, bool $isPublished): int
    {
        return Quiz::whereIn('id', $quizIds)
            ->update([
                'is_published' => $isPublished,
                'published_at' => $isPublished ? now() : null
            ]);
    }

    public function getQuizzesByCategories(array $categoryIds, int $perPage = 15): LengthAwarePaginator
    {
        return Quiz::with(['category'])
            ->whereIn('category_id', $categoryIds)
            ->where('is_published', true)
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }

    public function getHighSuccessQuizzes(float $minSuccessRate = 80, int $limit = 10): Collection
    {
        return Quiz::select('quizzes.*')
            ->join('attempts', 'quizzes.id', '=', 'attempts.quiz_id')
            ->where('attempts.status', 'completed')
            ->groupBy('quizzes.id')
            ->havingRaw('AVG(attempts.percentage_score) >= ?', [$minSuccessRate])
            ->havingRaw('COUNT(attempts.id) >= 10')
            ->limit($limit)
            ->get();
    }

    public function getQuizzesWithCompletionRates(int $limit = 10): Collection
    {
        return Quiz::select('quizzes.*')
            ->selectRaw('COUNT(CASE WHEN attempts.status = "completed" THEN 1 END) as completed_count')
            ->selectRaw('COUNT(attempts.id) as total_count')
            ->selectRaw('(COUNT(CASE WHEN attempts.status = "completed" THEN 1 END) / COUNT(attempts.id)) * 100 as completion_rate')
            ->leftJoin('attempts', 'quizzes.id', '=', 'attempts.quiz_id')
            ->groupBy('quizzes.id')
            ->havingRaw('COUNT(attempts.id) > 0')
            ->orderByRaw('completion_rate DESC')
            ->limit($limit)
            ->get();
    }

    public function getRandomQuizzes(int $limit = 5): Collection
    {
        return Quiz::with(['category'])
            ->where('is_published', true)
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public function getRecommendedForUser(int $userId, int $limit = 5): Collection
    {
        $userCategories = DB::table('attempts')
            ->join('quizzes', 'attempts.quiz_id', '=', 'quizzes.id')
            ->where('attempts.user_id', $userId)
            ->select('quizzes.category_id', DB::raw('COUNT(*) as count'))
            ->groupBy('quizzes.category_id')
            ->orderByDesc('count')
            ->pluck('category_id')
            ->toArray();
        
        if (empty($userCategories)) {
            return $this->getRandomQuizzes($limit);
        }
        
        return Quiz::with(['category'])
            ->where('is_published', true)
            ->whereIn('category_id', $userCategories)
            ->whereNotIn('id', function($query) use ($userId) {
                $query->select('quiz_id')
                    ->from('attempts')
                    ->where('user_id', $userId);
            })
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public function getSimilarDifficultyQuizzes(int $userId, int $limit = 5): Collection
    {
        $avgScore = DB::table('attempts')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->avg('percentage_score') ?? 50;
        
        $difficulty = 'medium';
        if ($avgScore >= 80) {
            $difficulty = 'hard';
        } elseif ($avgScore <= 40) {
            $difficulty = 'easy';
        }
        
        return Quiz::with(['category'])
            ->where('is_published', true)
            ->where('difficulty', $difficulty)
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public function getUnattemptedQuizzes(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Quiz::with(['category'])
            ->where('is_published', true)
            ->whereNotIn('id', function($query) use ($userId) {
                $query->select('quiz_id')
                    ->from('attempts')
                    ->where('user_id', $userId);
            })
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }

    public function getQuizzesWithPendingQuestions(): Collection
    {
        return Quiz::has('questions', '<', 5)
            ->orWhere('total_questions', 0)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getPerformanceTrend(int $quizId, int $days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();
        
        $trend = DB::table('attempts')
            ->where('quiz_id', $quizId)
            ->where('status', 'completed')
            ->where('completed_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(completed_at) as date'),
                DB::raw('AVG(percentage_score) as avg_score'),
                DB::raw('COUNT(*) as attempts')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
        
        return $trend;
    }

    public function getTopPerformingQuizzes(string $period = 'all', int $limit = 10): Collection
    {
        $query = Quiz::select('quizzes.*')
            ->join('attempts', 'quizzes.id', '=', 'attempts.quiz_id')
            ->where('attempts.status', 'completed');
        
        if ($period === 'weekly') {
            $query->where('attempts.completed_at', '>=', now()->subWeek());
        } elseif ($period === 'monthly') {
            $query->where('attempts.completed_at', '>=', now()->subMonth());
        }
        
        return $query->groupBy('quizzes.id')
            ->orderByRaw('AVG(attempts.percentage_score) DESC')
            ->limit($limit)
            ->get();
    }

    public function getAttemptDistribution(int $quizId, string $interval = 'day', int $limit = 30): array
    {
        $query = DB::table('attempts')
            ->where('quiz_id', $quizId);
        
        if ($interval === 'hour') {
            $query->select(
                DB::raw('HOUR(created_at) as period'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('period')
            ->orderBy('period');
        } elseif ($interval === 'day') {
            $query->select(
                DB::raw('DATE(created_at) as period'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->limit($limit);
        } elseif ($interval === 'month') {
            $query->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as period'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->limit($limit);
        }
        
        return $query->get()->toArray();
    }

    public function getAverageScoresByDifficulty(): array
    {
        return DB::table('attempts')
            ->join('quizzes', 'attempts.quiz_id', '=', 'quizzes.id')
            ->where('attempts.status', 'completed')
            ->select(
                'quizzes.difficulty',
                DB::raw('AVG(attempts.percentage_score) as avg_score'),
                DB::raw('COUNT(*) as total_attempts')
            )
            ->groupBy('quizzes.difficulty')
            ->get()
            ->toArray();
    }

    public function getCountByCategory(): array
    {
        return Quiz::select('category_id', DB::raw('COUNT(*) as count'))
            ->groupBy('category_id')
            ->with('category')
            ->get()
            ->toArray();
    }

    public function getCountByDifficulty(): array
    {
        return Quiz::select('difficulty', DB::raw('COUNT(*) as count'))
            ->groupBy('difficulty')
            ->get()
            ->toArray();
    }

    public function getQuizzesByDateRange(string $startDate, string $endDate): Collection
    {
        return Quiz::whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at')
            ->get();
    }

    public function getMostAttemptedQuizzes(int $limit = 10): Collection
    {
        return Quiz::withCount('attempts')
            ->orderBy('attempts_count', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getHighestRatedQuizzes(int $limit = 10, int $minAttempts = 10): Collection
    {
        return Quiz::select('quizzes.*')
            ->join('attempts', 'quizzes.id', '=', 'attempts.quiz_id')
            ->where('attempts.status', 'completed')
            ->groupBy('quizzes.id')
            ->havingRaw('COUNT(attempts.id) >= ?', [$minAttempts])
            ->orderByRaw('AVG(attempts.percentage_score) DESC')
            ->limit($limit)
            ->get();
    }

    public function getLowestRatedQuizzes(int $limit = 10, int $minAttempts = 10): Collection
    {
        return Quiz::select('quizzes.*')
            ->join('attempts', 'quizzes.id', '=', 'attempts.quiz_id')
            ->where('attempts.status', 'completed')
            ->groupBy('quizzes.id')
            ->havingRaw('COUNT(attempts.id) >= ?', [$minAttempts])
            ->orderByRaw('AVG(attempts.percentage_score) ASC')
            ->limit($limit)
            ->get();
    }

    public function getQuizzesWithFewQuestions(int $minQuestions = 5): Collection
    {
        return Quiz::has('questions', '<', $minQuestions)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getExpiredQuizzes(): Collection
    {
        return Quiz::where('expires_at', '<', now())
            ->where('is_published', true)
            ->get();
    }

    public function getScheduledQuizzes(): Collection
    {
        return Quiz::where('published_at', '>', now())
            ->where('is_published', false)
            ->get();
    }

    public function getCompletionStats(int $quizId): array
    {
        $total = DB::table('attempts')->where('quiz_id', $quizId)->count();
        $completed = DB::table('attempts')->where('quiz_id', $quizId)->where('status', 'completed')->count();
        $inProgress = DB::table('attempts')->where('quiz_id', $quizId)->where('status', 'in_progress')->count();
        $timedOut = DB::table('attempts')->where('quiz_id', $quizId)->where('status', 'timed_out')->count();
        
        return [
            'total' => $total,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'timed_out' => $timedOut,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
        ];
    }

    public function getQuestionDifficultyBreakdown(int $quizId): array
    {
        return DB::table('questions')
            ->where('quiz_id', $quizId)
            ->select('difficulty', DB::raw('COUNT(*) as count'))
            ->groupBy('difficulty')
            ->get()
            ->toArray();
    }

    public function getAverageTimeTaken(int $quizId): float
    {
        return (float) DB::table('attempts')
            ->where('quiz_id', $quizId)
            ->where('status', 'completed')
            ->avg('time_taken') ?? 0;
    }

    public function getPassRate(int $quizId): float
    {
        $quiz = $this->findById($quizId);
        if (!$quiz) {
            return 0;
        }
        
        $completed = DB::table('attempts')
            ->where('quiz_id', $quizId)
            ->where('status', 'completed')
            ->count();
        
        if ($completed === 0) {
            return 0;
        }
        
        $passed = DB::table('attempts')
            ->where('quiz_id', $quizId)
            ->where('status', 'completed')
            ->where('percentage_score', '>=', $quiz->passing_score)
            ->count();
        
        return round(($passed / $completed) * 100, 2);
    }

    public function getDropOffRate(int $quizId): float
    {
        $total = DB::table('attempts')->where('quiz_id', $quizId)->count();
        if ($total === 0) {
            return 0;
        }
        
        $completed = DB::table('attempts')->where('quiz_id', $quizId)->where('status', 'completed')->count();
        $dropped = $total - $completed;
        
        return round(($dropped / $total) * 100, 2);
    }

    public function getQuizzesByTag(string $tag, int $perPage = 15): LengthAwarePaginator
    {
        return Quiz::with(['category'])
            ->where('is_published', true)
            ->where(function($query) use ($tag) {
                $query->where('title', 'LIKE', "%{$tag}%")
                    ->orWhere('description', 'LIKE', "%{$tag}%");
            })
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }

    public function getRelatedQuizzes(int $quizId, int $limit = 5): Collection
    {
        $quiz = $this->findById($quizId);
        if (!$quiz) {
            return collect();
        }
        
        return Quiz::with(['category'])
            ->where('id', '!=', $quizId)
            ->where('category_id', $quiz->category_id)
            ->where('is_published', true)
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public function getSuggestionsForUser(int $userId, int $limit = 5): Collection
    {
        return $this->getRecommendedForUser($userId, $limit);
    }

    public function getUpcomingQuizzes(int $limit = 10): Collection
    {
        return Quiz::where('is_published', true)
            ->where('published_at', '>', now())
            ->orderBy('published_at')
            ->limit($limit)
            ->get();
    }

    public function getQuizLeaderboard(int $quizId, int $limit = 10): Collection
    {
        return DB::table('attempts')
            ->join('users', 'attempts.user_id', '=', 'users.id')
            ->where('attempts.quiz_id', $quizId)
            ->where('attempts.status', 'completed')
            ->select(
                'users.id',
                'users.name',
                DB::raw('MAX(attempts.percentage_score) as best_score'),
                DB::raw('MIN(attempts.time_taken) as fastest_time'),
                DB::raw('COUNT(*) as attempts_count')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('best_score')
            ->orderBy('fastest_time')
            ->limit($limit)
            ->get();
    }

    public function getUserRankForQuiz(int $quizId, int $userId): ?int
    {
        $userScore = DB::table('attempts')
            ->where('quiz_id', $quizId)
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->max('percentage_score');
        
        if (!$userScore) {
            return null;
        }
        
        $betterScores = DB::table('attempts')
            ->where('quiz_id', $quizId)
            ->where('status', 'completed')
            ->select('user_id', DB::raw('MAX(percentage_score) as best_score'))
            ->groupBy('user_id')
            ->having('best_score', '>', $userScore)
            ->count();
        
        return $betterScores + 1;
    }

    public function exportForReporting(array $filters = []): array
    {
        $query = Quiz::with(['category', 'questions']);
        
        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }
        
        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }
        
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        
        return $query->get()->map(function($quiz) {
            return [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'category' => $quiz->category->name ?? 'Uncategorized',
                'difficulty' => $quiz->difficulty,
                'questions_count' => $quiz->questions_count,
                'time_limit' => $quiz->time_limit,
                'is_published' => $quiz->is_published,
                'published_at' => $quiz->published_at,
                'created_at' => $quiz->created_at,
            ];
        })->toArray();
    }

    public function getQuizMetadata(int $quizId): array
    {
        $quiz = $this->findById($quizId);
        if (!$quiz) {
            return [];
        }
        
        return [
            'title' => $quiz->title,
            'slug' => $quiz->slug,
            'description' => $quiz->description,
            'category' => $quiz->category->name ?? null,
            'difficulty' => $quiz->difficulty,
            'time_limit' => $quiz->time_limit,
            'passing_score' => $quiz->passing_score,
            'questions_count' => $quiz->questions()->count(),
            'attempts_count' => $quiz->attempts()->count(),
        ];
    }

    public function updateQuizMetadata(int $quizId, array $metadata): bool
    {
        $quiz = $this->findById($quizId);
        if (!$quiz) {
            return false;
        }
        
        return $quiz->update($metadata);
    }

    public function getQuizzesByAuthor(int $authorId, int $perPage = 15): LengthAwarePaginator
    {
        return Quiz::where('created_by', $authorId)
            ->with(['category'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function cloneQuiz(int $quizId, string $newTitle): ?Quiz
    {
        DB::beginTransaction();
        try {
            $quiz = $this->findById($quizId);
            if (!$quiz) {
                return null;
            }
            
            $newQuiz = $quiz->replicate();
            $newQuiz->title = $newTitle;
            $newQuiz->slug = null;
            $newQuiz->is_published = false;
            $newQuiz->published_at = null;
            $newQuiz->save();
            
            foreach ($quiz->questions as $question) {
                $newQuestion = $question->replicate();
                $newQuestion->quiz_id = $newQuiz->id;
                $newQuestion->save();
            }
            
            $newQuiz->updateTotalQuestions();
            
            DB::commit();
            return $newQuiz;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getActivityTimeline(int $quizId, int $days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();
        
        $activity = DB::table('attempts')
            ->where('quiz_id', $quizId)
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as attempts'),
                DB::raw('COUNT(DISTINCT user_id) as unique_users')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
        
        return $activity;
    }

    public function getHighEngagementQuizzes(int $limit = 10): Collection
    {
        return Quiz::select('quizzes.*')
            ->selectRaw('COUNT(DISTINCT attempts.user_id) / DATEDIFF(NOW(), MIN(quizzes.created_at)) as engagement_score')
            ->leftJoin('attempts', 'quizzes.id', '=', 'attempts.quiz_id')
            ->groupBy('quizzes.id')
            ->orderByRaw('engagement_score DESC')
            ->limit($limit)
            ->get();
    }

    public function getLowEngagementQuizzes(int $limit = 10): Collection
    {
        return Quiz::select('quizzes.*')
            ->selectRaw('COUNT(DISTINCT attempts.user_id) / DATEDIFF(NOW(), MIN(quizzes.created_at)) as engagement_score')
            ->leftJoin('attempts', 'quizzes.id', '=', 'attempts.quiz_id')
            ->groupBy('quizzes.id')
            ->havingRaw('engagement_score < 0.1')
            ->orderByRaw('engagement_score ASC')
            ->limit($limit)
            ->get();
    }

    public function archiveOldQuizzes(int $daysUnused = 180): int
    {
        $cutoffDate = now()->subDays($daysUnused);
        
        $quizzes = Quiz::whereNotExists(function($query) {
                $query->select(DB::raw(1))
                    ->from('attempts')
                    ->whereRaw('attempts.quiz_id = quizzes.id');
            })
            ->where('created_at', '<', $cutoffDate)
            ->get();
        
        if ($quizzes->isEmpty()) {
            return 0;
        }
        
        DB::beginTransaction();
        try {
            foreach ($quizzes as $quiz) {
                DB::table('quizzes_archive')->insert([
                    'original_id' => $quiz->id,
                    'title' => $quiz->title,
                    'data' => json_encode($quiz->toArray()),
                    'archived_at' => now(),
                ]);
                $quiz->delete();
            }
            DB::commit();
            return $quizzes->count();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function restoreArchivedQuizzes(array $quizIds): int
    {
        // Implementation depends on archive table structure
        return 0;
    }

    public function getValidationStatus(int $quizId): array
    {
        $quiz = $this->getWithQuestions($quizId);
        if (!$quiz) {
            return ['valid' => false, 'errors' => ['Quiz not found']];
        }
        
        $errors = [];
        
        if ($quiz->questions->count() === 0) {
            $errors[] = 'Quiz has no questions';
        }
        
        foreach ($quiz->questions as $question) {
            if (count($question->options) < 2) {
                $errors[] = "Question {$question->id} has less than 2 options";
            }
            if (!isset($question->options[$question->correct_answer])) {
                $errors[] = "Question {$question->id} has invalid correct answer";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => [],
        ];
    }

    public function validateIntegrity(int $quizId): array
    {
        return $this->getValidationStatus($quizId);
    }

    public function getTrendingQuizzes(int $limit = 10): Collection
    {
        $startDate = now()->subDays(7);
        
        return Quiz::select('quizzes.*')
            ->join('attempts', 'quizzes.id', '=', 'attempts.quiz_id')
            ->where('attempts.created_at', '>=', $startDate)
            ->groupBy('quizzes.id')
            ->orderByRaw('COUNT(attempts.id) DESC')
            ->limit($limit)
            ->get();
    }

    public function getSeasonalQuizzes(string $season, int $limit = 10): Collection
    {
        return Quiz::with(['category'])
            ->where('is_published', true)
            ->where(function($query) use ($season) {
                $query->where('title', 'LIKE', "%{$season}%")
                    ->orWhere('description', 'LIKE', "%{$season}%");
            })
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public function getQuizzesByDifficultyRange(string $minDifficulty, string $maxDifficulty, int $perPage = 15): LengthAwarePaginator
    {
        $difficulties = ['beginner', 'intermediate', 'advanced', 'expert'];
        $minIndex = array_search($minDifficulty, $difficulties);
        $maxIndex = array_search($maxDifficulty, $difficulties);
        
        $allowedDifficulties = array_slice($difficulties, $minIndex, $maxIndex - $minIndex + 1);
        
        return Quiz::with(['category'])
            ->whereIn('difficulty', $allowedDifficulties)
            ->where('is_published', true)
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }

    public function getCompletionForecast(int $quizId): array
    {
        $attempts = DB::table('attempts')
            ->where('quiz_id', $quizId)
            ->orderBy('created_at')
            ->get();
        
        if ($attempts->isEmpty()) {
            return ['forecast' => 'insufficient_data'];
        }
        
        $avgPerDay = $attempts->groupBy(function($item) {
            return date('Y-m-d', strtotime($item->created_at));
        })->avg();
        
        return [
            'avg_daily_attempts' => round($avgPerDay, 2),
            'forecast_7_days' => round($avgPerDay * 7),
            'forecast_30_days' => round($avgPerDay * 30),
        ];
    }

    public function getPopularityIndex(int $quizId): float
    {
        $attempts = DB::table('attempts')->where('quiz_id', $quizId)->count();
        $uniqueUsers = DB::table('attempts')->where('quiz_id', $quizId)->distinct('user_id')->count('user_id');
        $daysSinceCreated = Quiz::find($quizId)?->created_at->diffInDays(now()) ?? 1;
        
        $popularity = ($attempts * 0.6 + $uniqueUsers * 0.4) / max($daysSinceCreated, 1);
        
        return round($popularity, 2);
    }

    public function getQualityScore(int $quizId): float
    {
        $quiz = $this->findById($quizId);
        if (!$quiz) {
            return 0;
        }
        
        $questionCount = $quiz->questions()->count();
        $hasExplanations = $quiz->questions()->whereNotNull('explanation')->count();
        $avgRating = DB::table('quiz_reviews')->where('quiz_id', $quizId)->avg('rating') ?? 0;
        $completionRate = $this->getCompletionStats($quizId)['completion_rate'] / 100;
        
        $score = ($questionCount / 20) * 0.3 + ($hasExplanations / max($questionCount, 1)) * 0.3 + ($avgRating / 5) * 0.2 + $completionRate * 0.2;
        
        return round(min($score, 1) * 100, 2);
    }

    public function getSimilarPerformanceQuizzes(int $quizId, int $limit = 5): Collection
    {
        $quizStats = $this->getQuizStats($quizId);
        
        return Quiz::select('quizzes.*')
            ->selectRaw('ABS(AVG(attempts.percentage_score) - ?) as score_difference', [$quizStats['average_score']])
            ->join('attempts', 'quizzes.id', '=', 'attempts.quiz_id')
            ->where('quizzes.id', '!=', $quizId)
            ->where('attempts.status', 'completed')
            ->groupBy('quizzes.id')
            ->orderBy('score_difference')
            ->limit($limit)
            ->get();
    }

    public function getOptimizationSuggestions(int $quizId): array
    {
        $suggestions = [];
        $quiz = $this->getWithQuestions($quizId);
        
        if (!$quiz) {
            return [];
        }
        
        if ($quiz->questions->count() < 10) {
            $suggestions[] = 'Add more questions to make the quiz more comprehensive';
        }
        
        $questionsWithoutExplanations = $quiz->questions()->whereNull('explanation')->count();
        if ($questionsWithoutExplanations > 0) {
            $suggestions[] = "Add explanations to {$questionsWithoutExplanations} questions to improve learning";
        }
        
        if (!$quiz->featured_image) {
            $suggestions[] = 'Add a featured image to make the quiz more attractive';
        }
        
        if ($quiz->time_limit < 15) {
            $suggestions[] = 'Consider increasing the time limit as the quiz has many questions';
        }
        
        return $suggestions;
    }

    public function getAccessibilityScore(int $quizId): float
    {
        $quiz = $this->findById($quizId);
        if (!$quiz) {
            return 0;
        }
        
        $score = 0;
        $checks = 0;
        
        if ($quiz->description) $score++;
        $checks++;
        
        if ($quiz->featured_image) $score++;
        $checks++;
        
        $questionsWithMedia = $quiz->questions()->whereNotNull('image_url')->count();
        $score += min($questionsWithMedia / 5, 1);
        $checks++;
        
        return round(($score / $checks) * 100, 2);
    }

    public function getEngagementMetrics(int $quizId): array
    {
        $attempts = DB::table('attempts')->where('quiz_id', $quizId);
        $totalAttempts = $attempts->count();
        $uniqueUsers = $attempts->distinct('user_id')->count('user_id');
        $avgTimeSpent = $attempts->where('status', 'completed')->avg('time_taken');
        $returnRate = DB::table('attempts')
            ->where('quiz_id', $quizId)
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();
        
        return [
            'total_attempts' => $totalAttempts,
            'unique_users' => $uniqueUsers,
            'avg_time_spent' => round($avgTimeSpent ?? 0, 2),
            'return_rate' => $uniqueUsers > 0 ? round(($returnRate / $uniqueUsers) * 100, 2) : 0,
            'attempts_per_user' => $uniqueUsers > 0 ? round($totalAttempts / $uniqueUsers, 2) : 0,
        ];
    }

    public function getRetentionRate(int $quizId): float
    {
        $attempts = DB::table('attempts')
            ->where('quiz_id', $quizId)
            ->select('user_id', DB::raw('COUNT(*) as attempt_count'))
            ->groupBy('user_id')
            ->get();
        
        if ($attempts->isEmpty()) {
            return 0;
        }
        
        $returningUsers = $attempts->filter(fn($a) => $a->attempt_count > 1)->count();
        $totalUsers = $attempts->count();
        
        return round(($returningUsers / $totalUsers) * 100, 2);
    }

    public function getSharingStats(int $quizId): array
    {
        // Implementation depends on sharing system
        return [
            'total_shares' => 0,
            'facebook' => 0,
            'twitter' => 0,
            'linkedin' => 0,
        ];
    }

    public function getFeedbackSummary(int $quizId): array
    {
        $reviews = DB::table('quiz_reviews')->where('quiz_id', $quizId);
        
        return [
            'total_reviews' => $reviews->count(),
            'average_rating' => round($reviews->avg('rating') ?? 0, 2),
            'rating_distribution' => [
                5 => $reviews->where('rating', 5)->count(),
                4 => $reviews->where('rating', 4)->count(),
                3 => $reviews->where('rating', 3)->count(),
                2 => $reviews->where('rating', 2)->count(),
                1 => $reviews->where('rating', 1)->count(),
            ],
        ];
    }

    public function getRevisionHistory(int $quizId): array
    {
        // Implementation depends on revision system
        return [];
    }

    public function restoreVersion(int $quizId, string $version): bool
    {
        // Implementation depends on version system
        return false;
    }

    public function compareQuizzes(int $quizId, array $otherQuizIds): array
    {
        $quiz = $this->getQuizStats($quizId);
        $comparisons = [];
        
        foreach ($otherQuizIds as $otherId) {
            $other = $this->getQuizStats($otherId);
            $comparisons[] = [
                'quiz_id' => $otherId,
                'stats' => $other,
                'difference' => [
                    'attempts' => $other['total_attempts'] - $quiz['total_attempts'],
                    'avg_score' => $other['average_score'] - $quiz['average_score'],
                ],
            ];
        }
        
        return $comparisons;
    }

    public function getLearningObjectives(int $quizId): array
    {
        // Implementation depends on learning objectives system
        return [];
    }

    public function updateLearningObjectives(int $quizId, array $objectives): bool
    {
        // Implementation depends on learning objectives system
        return false;
    }

    public function getPrerequisites(int $quizId): array
    {
        // Implementation depends on prerequisites system
        return [];
    }

    public function checkPrerequisites(int $quizId, int $userId): bool
    {
        // Implementation depends on prerequisites system
        return true;
    }

    public function getPathRecommendations(int $userId, string $path, int $limit = 5): Collection
    {
        // Implementation depends on learning path system
        return collect();
    }

    public function getSkillTags(int $quizId): array
    {
        // Implementation depends on skill tagging system
        return [];
    }

    public function updateSkillTags(int $quizId, array $skills): bool
    {
        // Implementation depends on skill tagging system
        return false;
    }

    public function getQuizzesBySkill(string $skill, int $level, int $perPage = 15): LengthAwarePaginator
    {
        // Implementation depends on skill system
        return Quiz::where('is_published', true)->paginate($perPage);
    }

    public function getCertificationInfo(int $quizId): ?array
    {
        // Implementation depends on certification system
        return null;
    }

    public function updateCertificationInfo(int $quizId, array $certInfo): bool
    {
        // Implementation depends on certification system
        return false;
    }

    public function getAvailableBadges(int $quizId): array
    {
        // Implementation depends on badge system
        return [];
    }

    public function awardBadge(int $quizId, int $userId, string $badge): bool
    {
        // Implementation depends on badge system
        return false;
    }

    public function getSchedule(int $quizId): ?array
    {
        // Implementation depends on scheduling system
        return null;
    }

    public function updateSchedule(int $quizId, array $schedule): bool
    {
        // Implementation depends on scheduling system
        return false;
    }

    public function getUpcomingScheduledQuizzes(int $limit = 10): Collection
    {
        // Implementation depends on scheduling system
        return collect();
    }

    public function getReminders(int $quizId): array
    {
        // Implementation depends on reminder system
        return [];
    }

    public function sendReminders(int $quizId): int
    {
        // Implementation depends on reminder system
        return 0;
    }

    public function getAnnouncement(int $quizId): ?string
    {
        // Implementation depends on announcement system
        return null;
    }

    public function updateAnnouncement(int $quizId, string $announcement): bool
    {
        // Implementation depends on announcement system
        return false;
    }

    public function getFaqs(int $quizId): array
    {
        // Implementation depends on FAQ system
        return [];
    }

    public function updateFaqs(int $quizId, array $faqs): bool
    {
        // Implementation depends on FAQ system
        return false;
    }

    public function getResources(int $quizId): array
    {
        // Implementation depends on resource system
        return [];
    }

    public function addResource(int $quizId, array $resource): bool
    {
        // Implementation depends on resource system
        return false;
    }

    public function removeResource(int $quizId, int $resourceId): bool
    {
        // Implementation depends on resource system
        return false;
    }

    public function getDiscussions(int $quizId, int $perPage = 20): LengthAwarePaginator
    {
        // Implementation depends on discussion system
        return new LengthAwarePaginator([], 0, $perPage);
    }

    public function addDiscussion(int $quizId, int $userId, string $content)
    {
        // Implementation depends on discussion system
        return null;
    }

    public function getReviews(int $quizId, int $perPage = 20): LengthAwarePaginator
    {
        // Implementation depends on review system
        return new LengthAwarePaginator([], 0, $perPage);
    }

    public function addReview(int $quizId, int $userId, int $rating, string $comment)
    {
        // Implementation depends on review system
        return null;
    }

    public function getAverageRating(int $quizId): float
    {
        return (float) DB::table('quiz_reviews')
            ->where('quiz_id', $quizId)
            ->avg('rating') ?? 0;
    }

    public function getRatingDistribution(int $quizId): array
    {
        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = DB::table('quiz_reviews')
                ->where('quiz_id', $quizId)
                ->where('rating', $i)
                ->count();
        }
        return $distribution;
    }

    public function getReports(int $quizId, string $type = 'all'): array
    {
        // Implementation depends on reporting system
        return [];
    }

    public function reportIssue(int $quizId, int $userId, string $issue, string $description)
    {
        // Implementation depends on issue tracking system
        return null;
    }

    public function resolveIssue(int $reportId, string $resolution): bool
    {
        // Implementation depends on issue tracking system
        return false;
    }

    public function getAnalyticsExport(int $quizId, array $options = []): array
    {
        return [
            'quiz_stats' => $this->getQuizStats($quizId),
            'engagement' => $this->getEngagementMetrics($quizId),
            'performance_trend' => $this->getPerformanceTrend($quizId, 30),
            'feedback' => $this->getFeedbackSummary($quizId),
        ];
    }

    public function getPerformanceBenchmarks(int $quizId): array
    {
        $quiz = $this->findById($quizId);
        if (!$quiz) {
            return [];
        }
        
        $categoryAvg = DB::table('attempts')
            ->join('quizzes', 'attempts.quiz_id', '=', 'quizzes.id')
            ->where('quizzes.category_id', $quiz->category_id)
            ->where('attempts.status', 'completed')
            ->avg('attempts.percentage_score');
        
        $difficultyAvg = DB::table('attempts')
            ->join('quizzes', 'attempts.quiz_id', '=', 'quizzes.id')
            ->where('quizzes.difficulty', $quiz->difficulty)
            ->where('attempts.status', 'completed')
            ->avg('attempts.percentage_score');
        
        return [
            'category_average' => round($categoryAvg ?? 0, 2),
            'difficulty_average' => round($difficultyAvg ?? 0, 2),
            'global_average' => round(DB::table('attempts')->where('status', 'completed')->avg('percentage_score') ?? 0, 2),
        ];
    }

    public function compareWithBenchmarks(int $quizId): array
    {
        $quizStats = $this->getQuizStats($quizId);
        $benchmarks = $this->getPerformanceBenchmarks($quizId);
        
        return [
            'vs_category' => round($quizStats['average_score'] - $benchmarks['category_average'], 2),
            'vs_difficulty' => round($quizStats['average_score'] - $benchmarks['difficulty_average'], 2),
            'vs_global' => round($quizStats['average_score'] - $benchmarks['global_average'], 2),
        ];
    }

    public function getImprovementPlan(int $quizId): array
    {
        return [
            'short_term' => $this->getOptimizationSuggestions($quizId),
            'long_term' => [
                'Increase question pool',
                'Add multimedia content',
                'Create certification path',
            ],
        ];
    }

    public function implementImprovement(int $quizId, string $suggestion): bool
    {
        // Implementation depends on improvement system
        return false;
    }

    public function getAbTestResults(int $quizId): array
    {
        // Implementation depends on A/B testing system
        return [];
    }

    public function createAbTest(int $quizId, array $variants)
    {
        // Implementation depends on A/B testing system
        return null;
    }

    public function getExperimentResults(int $quizId): array
    {
        // Implementation depends on experiment system
        return [];
    }

    public function runExperiment(int $quizId, string $experiment)
    {
        // Implementation depends on experiment system
        return null;
    }

    public function getPersonalizationRules(int $quizId): array
    {
        // Implementation depends on personalization system
        return [];
    }

    public function updatePersonalizationRules(int $quizId, array $rules): bool
    {
        // Implementation depends on personalization system
        return false;
    }

    public function getPersonalizedQuiz(int $quizId, int $userId): array
    {
        $quiz = $this->findById($quizId);
        if (!$quiz) {
            return [];
        }
        
        $userLevel = DB::table('attempts')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->avg('percentage_score') ?? 50;
        
        $result = $quiz->toArray();
        
        if ($userLevel > 80) {
            $result['difficulty'] = $this->getNextDifficultyLevel($quiz->difficulty, 'up');
        } elseif ($userLevel < 40) {
            $result['difficulty'] = $this->getNextDifficultyLevel($quiz->difficulty, 'down');
        }
        
        return $result;
    }

    public function getAdaptivePath(int $quizId, int $userId): array
    {
        // Implementation depends on adaptive learning system
        return [];
    }

    public function updateAdaptivePath(int $quizId, int $userId, array $path): bool
    {
        // Implementation depends on adaptive learning system
        return false;
    }

    public function getGamificationElements(int $quizId): array
    {
        // Implementation depends on gamification system
        return [];
    }

    public function updateGamificationElements(int $quizId, array $elements): bool
    {
        // Implementation depends on gamification system
        return false;
    }

    public function getLeaderboardSettings(int $quizId): array
    {
        // Implementation depends on leaderboard system
        return [];
    }

    public function updateLeaderboardSettings(int $quizId, array $settings): bool
    {
        // Implementation depends on leaderboard system
        return false;
    }

    public function getAchievementSettings(int $quizId): array
    {
        // Implementation depends on achievement system
        return [];
    }

    public function updateAchievementSettings(int $quizId, array $settings): bool
    {
        // Implementation depends on achievement system
        return false;
    }

    public function getRewardSettings(int $quizId): array
    {
        // Implementation depends on reward system
        return [];
    }

    public function updateRewardSettings(int $quizId, array $settings): bool
    {
        // Implementation depends on reward system
        return false;
    }

    public function getNotificationSettings(int $quizId): array
    {
        // Implementation depends on notification system
        return [];
    }

    public function updateNotificationSettings(int $quizId, array $settings): bool
    {
        // Implementation depends on notification system
        return false;
    }

    public function getPrivacySettings(int $quizId): array
    {
        // Implementation depends on privacy system
        return [];
    }

    public function updatePrivacySettings(int $quizId, array $settings): bool
    {
        // Implementation depends on privacy system
        return false;
    }

    public function getCollaborationSettings(int $quizId): array
    {
        // Implementation depends on collaboration system
        return [];
    }

    public function updateCollaborationSettings(int $quizId, array $settings): bool
    {
        // Implementation depends on collaboration system
        return false;
    }

    public function getCollaborators(int $quizId): Collection
    {
        // Implementation depends on collaboration system
        return collect();
    }

    public function addCollaborator(int $quizId, int $userId, string $role): bool
    {
        // Implementation depends on collaboration system
        return false;
    }

    public function removeCollaborator(int $quizId, int $userId): bool
    {
        // Implementation depends on collaboration system
        return false;
    }

    public function getVersionHistory(int $quizId): array
    {
        // Implementation depends on version system
        return [];
    }

    public function createVersion(int $quizId, string $versionName): bool
    {
        // Implementation depends on version system
        return false;
    }

    public function compareVersions(int $quizId, string $version1, string $version2): array
    {
        // Implementation depends on version system
        return [];
    }

    public function getTranslation(int $quizId, string $language): ?array
    {
        // Implementation depends on translation system
        return null;
    }

    public function addTranslation(int $quizId, string $language, array $translation): bool
    {
        // Implementation depends on translation system
        return false;
    }

    public function updateTranslation(int $quizId, string $language, array $translation): bool
    {
        // Implementation depends on translation system
        return false;
    }

    public function getAvailableLanguages(int $quizId): array
    {
        // Implementation depends on translation system
        return [];
    }

    public function getAccessibilityFeatures(int $quizId): array
    {
        // Implementation depends on accessibility system
        return [];
    }

    public function updateAccessibilityFeatures(int $quizId, array $features): bool
    {
        // Implementation depends on accessibility system
        return false;
    }

    public function getComplianceInfo(int $quizId): array
    {
        // Implementation depends on compliance system
        return [];
    }

    public function updateComplianceInfo(int $quizId, array $compliance): bool
    {
        // Implementation depends on compliance system
        return false;
    }

    public function getAuditLog(int $quizId, int $limit = 100): Collection
    {
        // Implementation depends on audit system
        return collect();
    }

    public function logAction(int $quizId, int $userId, string $action, array $details = []): bool
    {
        // Implementation depends on audit system
        return false;
    }

    public function getBackup(int $quizId): ?array
    {
        // Implementation depends on backup system
        return null;
    }

    public function createBackup(int $quizId): bool
    {
        // Implementation depends on backup system
        return false;
    }

    public function restoreFromBackup(int $quizId, string $backupId): bool
    {
        // Implementation depends on backup system
        return false;
    }

    public function getHealthStatus(int $quizId): array
    {
        $validation = $this->getValidationStatus($quizId);
        
        return [
            'status' => $validation['valid'] ? 'healthy' : 'issues_detected',
            'issues' => $validation['errors'],
            'last_checked' => now()->toDateTimeString(),
        ];
    }

    public function runHealthCheck(int $quizId): array
    {
        return $this->getHealthStatus($quizId);
    }

    public function fixHealthIssues(int $quizId, array $issues): bool
    {
        // Implementation depends on issue fixing
        return false;
    }

    public function getPerformanceAlerts(int $quizId): array
    {
        $stats = $this->getQuizStats($quizId);
        $alerts = [];
        
        if ($stats['completion_rate'] < 30) {
            $alerts[] = 'Low completion rate detected';
        }
        
        if ($stats['average_score'] < 50) {
            $alerts[] = 'Quiz might be too difficult';
        }
        
        return $alerts;
    }

    public function acknowledgeAlert(int $alertId): bool
    {
        // Implementation depends on alert system
        return false;
    }

    public function getMaintenanceSchedule(int $quizId): ?array
    {
        // Implementation depends on maintenance system
        return null;
    }

    public function updateMaintenanceSchedule(int $quizId, array $schedule): bool
    {
        // Implementation depends on maintenance system
        return false;
    }

    public function performMaintenance(int $quizId, string $type): bool
    {
        // Implementation depends on maintenance system
        return false;
    }

    public function getDeprecationStatus(int $quizId): array
    {
        $quiz = $this->findById($quizId);
        if (!$quiz) {
            return ['deprecated' => false];
        }
        
        $daysSinceLastAttempt = DB::table('attempts')
            ->where('quiz_id', $quizId)
            ->max('created_at');
        
        $inactiveDays = $daysSinceLastAttempt ? now()->diffInDays($daysSinceLastAttempt) : 999;
        
        return [
            'deprecated' => $inactiveDays > 180,
            'reason' => $inactiveDays > 180 ? 'No attempts in last 6 months' : null,
            'inactive_days' => $inactiveDays,
        ];
    }

    public function deprecateQuiz(int $quizId, string $reason): bool
    {
        $quiz = $this->findById($quizId);
        if (!$quiz) {
            return false;
        }
        
        $quiz->is_published = false;
        $quiz->deprecated_at = now();
        $quiz->deprecation_reason = $reason;
        $quiz->save();
        
        return true;
    }

    public function undeprecateQuiz(int $quizId): bool
    {
        $quiz = $this->findById($quizId);
        if (!$quiz) {
            return false;
        }
        
        $quiz->is_published = true;
        $quiz->deprecated_at = null;
        $quiz->deprecation_reason = null;
        $quiz->save();
        
        return true;
    }

    public function getReplacementSuggestions(int $quizId): Collection
    {
        $quiz = $this->findById($quizId);
        if (!$quiz) {
            return collect();
        }
        
        return Quiz::where('category_id', $quiz->category_id)
            ->where('difficulty', $quiz->difficulty)
            ->where('id', '!=', $quizId)
            ->where('is_published', true)
            ->limit(5)
            ->get();
    }

    public function migrateQuizData(int $sourceQuizId, int $targetQuizId): bool
    {
        DB::beginTransaction();
        try {
            DB::table('attempts')
                ->where('quiz_id', $sourceQuizId)
                ->update(['quiz_id' => $targetQuizId]);
            
            $this->updateTotalQuestions($sourceQuizId);
            $this->updateTotalQuestions($targetQuizId);
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function getDataRetentionPolicy(int $quizId): array
    {
        // Implementation depends on data retention system
        return [
            'keep_attempts_days' => 365,
            'keep_reviews_days' => 730,
            'anonymize_data_days' => 90,
        ];
    }

    public function updateDataRetentionPolicy(int $quizId, array $policy): bool
    {
        // Implementation depends on data retention system
        return false;
    }

    public function purgeOldData(int $quizId, string $dataType, int $daysOld = 365): int
    {
        $cutoffDate = now()->subDays($daysOld);
        
        if ($dataType === 'attempts') {
            return DB::table('attempts')
                ->where('quiz_id', $quizId)
                ->where('created_at', '<', $cutoffDate)
                ->delete();
        }
        
        return 0;
    }

    public function getExportFormats(int $quizId): array
    {
        return ['json', 'csv', 'xml', 'pdf'];
    }

    public function exportQuiz(int $quizId, string $format)
    {
        $quiz = $this->getWithQuestions($quizId);
        if (!$quiz) {
            return null;
        }
        
        $data = [
            'quiz' => $quiz->toArray(),
            'questions' => $quiz->questions->toArray(),
        ];
        
        if ($format === 'json') {
            return json_encode($data);
        } elseif ($format === 'csv') {
            // CSV export logic
            return $data;
        }
        
        return $data;
    }

    public function importQuiz(string $format, $data): ?Quiz
    {
        // Implementation depends on import format
        return null;
    }

    public function getQuizTemplate(int $quizId): array
    {
        $quiz = $this->findById($quizId);
        if (!$quiz) {
            return [];
        }
        
        return [
            'title' => $quiz->title,
            'description' => $quiz->description,
            'category_id' => $quiz->category_id,
            'difficulty' => $quiz->difficulty,
            'time_limit' => $quiz->time_limit,
            'passing_score' => $quiz->passing_score,
            'points_per_question' => $quiz->points_per_question,
        ];
    }

    public function saveAsTemplate(int $quizId, string $templateName): bool
    {
        // Implementation depends on template system
        return false;
    }

    public function createFromTemplate(string $templateName, array $customizations = []): ?Quiz
    {
        // Implementation depends on template system
        return null;
    }

    public function getAvailableTemplates(): array
    {
        // Implementation depends on template system
        return [];
    }

    public function deleteTemplate(string $templateName): bool
    {
        // Implementation depends on template system
        return false;
    }

    public function getQuizBlueprint(int $quizId): array
    {
        // Implementation depends on blueprint system
        return [];
    }

    public function generateFromBlueprint(array $blueprint): ?Quiz
    {
        // Implementation depends on blueprint system
        return null;
    }

    public function validateBlueprint(array $blueprint): array
    {
        $errors = [];
        
        if (empty($blueprint['title'])) {
            $errors[] = 'Title is required';
        }
        
        if (empty($blueprint['questions']) || count($blueprint['questions']) < 1) {
            $errors[] = 'At least one question is required';
        }
        
        return $errors;
    }

    public function getScorecard(int $quizId): array
    {
        // Implementation depends on scorecard system
        return [];
    }

    public function updateScorecard(int $quizId, array $scorecard): bool
    {
        // Implementation depends on scorecard system
        return false;
    }

    public function getRubric(int $quizId): array
    {
        // Implementation depends on rubric system
        return [];
    }

    public function updateRubric(int $quizId, array $rubric): bool
    {
        // Implementation depends on rubric system
        return false;
    }

    public function evaluateWithRubric(int $quizId, int $questionId, string $answer): array
    {
        // Implementation depends on rubric system
        return ['score' => 0, 'feedback' => ''];
    }

    public function getFeedbackForm(int $quizId): array
    {
        // Implementation depends on feedback form system
        return [];
    }

    public function updateFeedbackForm(int $quizId, array $form): bool
    {
        // Implementation depends on feedback form system
        return false;
    }

    public function submitFeedback(int $quizId, int $userId, array $feedback): bool
    {
        // Implementation depends on feedback system
        return false;
    }

    public function getFeedbackAnalytics(int $quizId): array
    {
        // Implementation depends on feedback system
        return [];
    }

    public function getSurvey(int $quizId): array
    {
        // Implementation depends on survey system
        return [];
    }

    public function updateSurvey(int $quizId, array $survey): bool
    {
        // Implementation depends on survey system
        return false;
    }

    public function submitSurvey(int $quizId, int $userId, array $responses): bool
    {
        // Implementation depends on survey system
        return false;
    }

    public function getSurveyAnalytics(int $quizId): array
    {
        // Implementation depends on survey system
        return [];
    }

    public function getPoll(int $quizId): array
    {
        // Implementation depends on poll system
        return [];
    }

    public function updatePoll(int $quizId, array $poll): bool
    {
        // Implementation depends on poll system
        return false;
    }

    public function submitPollVote(int $quizId, int $userId, string $option): bool
    {
        // Implementation depends on poll system
        return false;
    }

    public function getPollResults(int $quizId): array
    {
        // Implementation depends on poll system
        return [];
    }

    public function getCompetition(int $quizId): ?array
    {
        // Implementation depends on competition system
        return null;
    }

    public function createCompetition(int $quizId, array $settings)
    {
        // Implementation depends on competition system
        return null;
    }

    public function joinCompetition(int $competitionId, int $userId): bool
    {
        // Implementation depends on competition system
        return false;
    }

    public function getCompetitionLeaderboard(int $competitionId): Collection
    {
        // Implementation depends on competition system
        return collect();
    }

    public function getCompetitionResults(int $competitionId): array
    {
        // Implementation depends on competition system
        return [];
    }

    public function getTournament(int $quizId): ?array
    {
        // Implementation depends on tournament system
        return null;
    }

    public function createTournament(int $quizId, array $settings)
    {
        // Implementation depends on tournament system
        return null;
    }

    public function getTournamentBracket(int $tournamentId): array
    {
        // Implementation depends on tournament system
        return [];
    }

    public function updateMatchResult(int $matchId, int $winnerId): bool
    {
        // Implementation depends on tournament system
        return false;
    }

    public function getTournamentWinner(int $tournamentId): ?array
    {
        // Implementation depends on tournament system
        return null;
    }

    private function getNextDifficultyLevel(string $current, string $direction): string
    {
        $levels = ['beginner', 'intermediate', 'advanced', 'expert'];
        $index = array_search($current, $levels);
        
        if ($index === false) {
            return 'intermediate';
        }
        
        if ($direction === 'up') {
            return $levels[min($index + 1, count($levels) - 1)];
        }
        
        return $levels[max($index - 1, 0)];
    }

    private function clearCache(?int $id = null, ?string $slug = null): void
    {
        if ($id) {
            Cache::forget("quiz.id.{$id}");
            Cache::forget("quiz.with_questions.{$id}");
            Cache::forget("quiz.stats.{$id}");
        }
        
        if ($slug) {
            Cache::forget("quiz.slug.{$slug}");
        }
        
        Cache::forget("queries.quizzes.published.*");
    }
}