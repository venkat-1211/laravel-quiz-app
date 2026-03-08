<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function findById(int $id): ?Category
    {
        return Cache::remember("category.id.{$id}", 3600, function () use ($id) {
            return Category::withCount('quizzes')->find($id);
        });
    }

    public function findBySlug(string $slug): ?Category
    {
        return Cache::remember("category.slug.{$slug}", 3600, function () use ($slug) {
            return Category::withCount('quizzes')->where('slug', $slug)->first();
        });
    }

    public function create(array $data): Category
    {
        $category = Category::create($data);
        $this->clearCache();
        return $category;
    }

    public function update(int $id, array $data): Category
    {
        $category = $this->findById($id);
        if (!$category) {
            throw new \Exception("Category not found");
        }
        $category->update($data);
        $this->clearCache($id, $category->slug);
        return $category;
    }

    public function delete(int $id): bool
    {
        $category = $this->findById($id);
        if (!$category) {
            return false;
        }
        $result = $category->delete();
        $this->clearCache($id, $category->slug);
        return $result;
    }

    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        return Category::withCount('quizzes')
            ->orderBy('order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function getActive(): array
    {
        return Category::where('is_active', true)
            ->withCount('quizzes')
            ->orderBy('order')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    public function getWithQuizCounts(): array
    {
        return Category::withCount('quizzes')
            ->orderBy('order')
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'icon' => $category->icon,
                    'quizzes_count' => $category->quizzes_count,
                    'is_active' => $category->is_active,
                    'order' => $category->order,
                    'parent_id' => $category->parent_id,
                ];
            })
            ->toArray();
    }

    public function getPopularCategories(int $limit = 5): array
    {
        return DB::table('categories')
            ->select(
                'categories.id',
                'categories.name',
                'categories.slug',
                'categories.icon',
                DB::raw('COUNT(attempts.id) as attempts_count'),
                DB::raw('COUNT(DISTINCT attempts.user_id) as unique_users')
            )
            ->leftJoin('quizzes', 'categories.id', '=', 'quizzes.category_id')
            ->leftJoin('attempts', 'quizzes.id', '=', 'attempts.quiz_id')
            ->where('categories.is_active', true)
            ->where('attempts.status', 'completed')
            ->groupBy('categories.id', 'categories.name', 'categories.slug', 'categories.icon')
            ->orderByDesc('attempts_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getCategoryStats(int $categoryId): array
    {
        $stats = DB::table('categories')
            ->select(
                DB::raw('COUNT(DISTINCT quizzes.id) as total_quizzes'),
                DB::raw('COUNT(DISTINCT attempts.id) as total_attempts'),
                DB::raw('COUNT(DISTINCT attempts.user_id) as unique_users'),
                DB::raw('AVG(attempts.percentage_score) as average_score'),
                DB::raw('SUM(attempts.score) as total_points')
            )
            ->leftJoin('quizzes', 'categories.id', '=', 'quizzes.category_id')
            ->leftJoin('attempts', 'quizzes.id', '=', 'attempts.quiz_id')
            ->where('categories.id', $categoryId)
            ->where('attempts.status', 'completed')
            ->first();

        return [
            'total_quizzes' => (int) ($stats->total_quizzes ?? 0),
            'total_attempts' => (int) ($stats->total_attempts ?? 0),
            'unique_users' => (int) ($stats->unique_users ?? 0),
            'average_score' => round($stats->average_score ?? 0, 2),
            'total_points' => (int) ($stats->total_points ?? 0),
        ];
    }

    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return Category::where('name', 'LIKE', "%{$query}%")
            ->orWhere('description', 'LIKE', "%{$query}%")
            ->withCount('quizzes')
            ->orderBy('order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function getByParent(?int $parentId = null): array
    {
        $query = Category::where('is_active', true)
            ->withCount('quizzes')
            ->orderBy('order')
            ->orderBy('name');

        if ($parentId === null) {
            $query->whereNull('parent_id');
        } else {
            $query->where('parent_id', $parentId);
        }

        return $query->get()->toArray();
    }

    public function reorder(array $order): bool
    {
        DB::beginTransaction();
        try {
            foreach ($order as $item) {
                Category::where('id', $item['id'])
                    ->update(['order' => $item['order']]);
            }
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function bulkUpdateStatus(array $categoryIds, bool $isActive): int
    {
        return Category::whereIn('id', $categoryIds)
            ->update(['is_active' => $isActive]);
    }

    public function getChildrenWithQuizCounts(int $parentId): array
    {
        return Category::where('parent_id', $parentId)
            ->withCount('quizzes')
            ->orderBy('order')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    public function getCategoryHierarchy(): array
    {
        $categories = Category::withCount('quizzes')
            ->orderBy('order')
            ->orderBy('name')
            ->get()
            ->toArray();

        return $this->buildTree($categories);
    }

    public function getCategoriesWithRecentActivity(int $days = 7): array
    {
        $startDate = now()->subDays($days);

        return DB::table('categories')
            ->select(
                'categories.id',
                'categories.name',
                'categories.slug',
                DB::raw('COUNT(DISTINCT attempts.id) as recent_attempts'),
                DB::raw('COUNT(DISTINCT attempts.user_id) as recent_users')
            )
            ->leftJoin('quizzes', 'categories.id', '=', 'quizzes.category_id')
            ->leftJoin('attempts', 'quizzes.id', '=', 'attempts.quiz_id')
            ->where('attempts.created_at', '>=', $startDate)
            ->where('attempts.status', 'completed')
            ->groupBy('categories.id', 'categories.name', 'categories.slug')
            ->orderByDesc('recent_attempts')
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function getCategoriesWithLowActivity(int $threshold = 10): array
    {
        return DB::table('categories')
            ->select(
                'categories.id',
                'categories.name',
                'categories.slug',
                DB::raw('COUNT(attempts.id) as total_attempts')
            )
            ->leftJoin('quizzes', 'categories.id', '=', 'quizzes.category_id')
            ->leftJoin('attempts', 'quizzes.id', '=', 'attempts.quiz_id')
            ->groupBy('categories.id', 'categories.name', 'categories.slug')
            ->havingRaw('total_attempts < ?', [$threshold])
            ->orderBy('total_attempts')
            ->get()
            ->toArray();
    }

    public function getCategoryPerformanceOverTime(int $categoryId, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        return DB::table('attempts')
            ->join('quizzes', 'attempts.quiz_id', '=', 'quizzes.id')
            ->where('quizzes.category_id', $categoryId)
            ->where('attempts.status', 'completed')
            ->where('attempts.created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(attempts.created_at) as date'),
                DB::raw('COUNT(*) as attempts_count'),
                DB::raw('AVG(attempts.percentage_score) as avg_score')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    public function getCategoryLeaderboard(int $categoryId, int $limit = 10): array
    {
        return DB::table('attempts')
            ->join('users', 'attempts.user_id', '=', 'users.id')
            ->join('quizzes', 'attempts.quiz_id', '=', 'quizzes.id')
            ->where('quizzes.category_id', $categoryId)
            ->where('attempts.status', 'completed')
            ->select(
                'users.id',
                'users.name',
                'users.avatar',
                DB::raw('MAX(attempts.percentage_score) as best_score'),
                DB::raw('AVG(attempts.percentage_score) as avg_score'),
                DB::raw('COUNT(*) as attempts_count'),
                DB::raw('SUM(attempts.score) as total_points')
            )
            ->groupBy('users.id', 'users.name', 'users.avatar')
            ->orderByDesc('total_points')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getSimilarCategories(int $categoryId, int $limit = 5): array
    {
        $category = $this->findById($categoryId);
        
        if (!$category) {
            return [];
        }

        return Category::where('id', '!=', $categoryId)
            ->where('is_active', true)
            ->where(function ($query) use ($category) {
                $query->where('name', 'LIKE', '%' . substr($category->name, 0, 3) . '%')
                    ->orWhere('description', 'LIKE', '%' . substr($category->description ?? '', 0, 20) . '%');
            })
            ->withCount('quizzes')
            ->orderBy('quizzes_count', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function exportForReporting(array $filters = []): array
    {
        $query = Category::withCount('quizzes')
            ->withCount(['quizzes as active_quizzes_count' => function ($q) {
                $q->where('is_published', true);
            }]);

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['has_quizzes'])) {
            if ($filters['has_quizzes']) {
                $query->having('quizzes_count', '>', 0);
            } else {
                $query->having('quizzes_count', '=', 0);
            }
        }

        return $query->orderBy('order')
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'icon' => $category->icon,
                    'is_active' => $category->is_active,
                    'order' => $category->order,
                    'parent_id' => $category->parent_id,
                    'total_quizzes' => $category->quizzes_count,
                    'active_quizzes' => $category->active_quizzes_count,
                    'created_at' => $category->created_at->toDateTimeString(),
                    'updated_at' => $category->updated_at->toDateTimeString(),
                ];
            })
            ->toArray();
    }

    public function getCategoryUsageStats(): array
    {
        return DB::table('categories')
            ->select(
                'categories.id',
                'categories.name',
                DB::raw('COUNT(DISTINCT quizzes.id) as quizzes_count'),
                DB::raw('COUNT(DISTINCT attempts.id) as attempts_count'),
                DB::raw('COUNT(DISTINCT attempts.user_id) as users_count')
            )
            ->leftJoin('quizzes', 'categories.id', '=', 'quizzes.category_id')
            ->leftJoin('attempts', 'quizzes.id', '=', 'attempts.quiz_id')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('attempts_count')
            ->get()
            ->toArray();
    }

    public function getCategoryGrowth(int $months = 6): array
    {
        $result = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $startOfMonth = $month->copy()->startOfMonth();
            $endOfMonth = $month->copy()->endOfMonth();
            
            $count = Category::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
            
            $result[] = [
                'month' => $month->format('Y-m'),
                'new_categories' => $count,
            ];
        }
        
        return $result;
    }

    public function getCategoryHierarchyStats(): array
    {
        $stats = DB::table('categories as c1')
            ->select(
                DB::raw('COUNT(DISTINCT c1.id) as total_categories'),
                DB::raw('COUNT(DISTINCT c2.id) as subcategories_count'),
                DB::raw('AVG(CASE WHEN c1.parent_id IS NULL THEN 1 ELSE 0 END) as root_percentage')
            )
            ->leftJoin('categories as c2', 'c1.id', '=', 'c2.parent_id')
            ->first();

        return [
            'total_categories' => (int) ($stats->total_categories ?? 0),
            'root_categories' => Category::whereNull('parent_id')->count(),
            'subcategories' => (int) ($stats->subcategories_count ?? 0),
            'avg_depth' => $this->calculateAvgDepth(),
            'max_depth' => $this->calculateMaxDepth(),
        ];
    }

    public function getCategoriesNeedingReview(): array
    {
        return Category::where('is_active', false)
            ->orWhereDoesntHave('quizzes')
            ->orWhereHas('quizzes', function ($q) {
                $q->havingRaw('COUNT(*) < 3');
            })
            ->withCount('quizzes')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'quizzes_count' => $category->quizzes_count,
                    'is_active' => $category->is_active,
                    'issues' => $this->getCategoryIssues($category),
                ];
            })
            ->toArray();
    }

    public function getCategorySuggestions(string $input, int $limit = 5): array
    {
        return Category::where('name', 'LIKE', "%{$input}%")
            ->orWhere('description', 'LIKE', "%{$input}%")
            ->where('is_active', true)
            ->withCount('quizzes')
            ->orderBy('quizzes_count', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getRelatedCategories(int $categoryId, int $limit = 5): array
    {
        $category = $this->findById($categoryId);
        
        if (!$category) {
            return [];
        }

        return DB::table('categories as c1')
            ->join('quizzes as q1', 'c1.id', '=', 'q1.category_id')
            ->join('attempts as a', 'q1.id', '=', 'a.quiz_id')
            ->join('quizzes as q2', 'a.quiz_id', '=', 'q2.id')
            ->join('categories as c2', 'q2.category_id', '=', 'c2.id')
            ->where('c1.id', $categoryId)
            ->where('c2.id', '!=', $categoryId)
            ->where('c2.is_active', true)
            ->select('c2.id', 'c2.name', 'c2.slug', DB::raw('COUNT(*) as co_occurrence'))
            ->groupBy('c2.id', 'c2.name', 'c2.slug')
            ->orderByDesc('co_occurrence')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getCategoryPerformanceMetrics(int $categoryId): array
    {
        $metrics = DB::table('attempts')
            ->join('quizzes', 'attempts.quiz_id', '=', 'quizzes.id')
            ->where('quizzes.category_id', $categoryId)
            ->where('attempts.status', 'completed')
            ->select(
                DB::raw('AVG(attempts.percentage_score) as avg_score'),
                DB::raw('STDDEV(attempts.percentage_score) as score_stddev'),
                DB::raw('MIN(attempts.percentage_score) as min_score'),
                DB::raw('MAX(attempts.percentage_score) as max_score'),
                DB::raw('AVG(attempts.time_taken) as avg_time'),
                DB::raw('PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY attempts.percentage_score) as median_score')
            )
            ->first();

        return [
            'avg_score' => round($metrics->avg_score ?? 0, 2),
            'median_score' => round($metrics->median_score ?? 0, 2),
            'score_stddev' => round($metrics->score_stddev ?? 0, 2),
            'min_score' => round($metrics->min_score ?? 0, 2),
            'max_score' => round($metrics->max_score ?? 0, 2),
            'avg_time' => round($metrics->avg_time ?? 0, 2),
        ];
    }

    public function getCategoryEngagementMetrics(int $categoryId): array
    {
        $totalQuizzes = DB::table('quizzes')->where('category_id', $categoryId)->count();
        
        $metrics = DB::table('attempts')
            ->join('quizzes', 'attempts.quiz_id', '=', 'quizzes.id')
            ->where('quizzes.category_id', $categoryId)
            ->where('attempts.status', 'completed')
            ->select(
                DB::raw('COUNT(DISTINCT attempts.user_id) as unique_users'),
                DB::raw('COUNT(*) as total_attempts'),
                DB::raw('AVG(attempts.percentage_score) as avg_score'),
                DB::raw('COUNT(*) / NULLIF(COUNT(DISTINCT attempts.user_id), 0) as attempts_per_user')
            )
            ->first();

        $returnRate = DB::table('attempts as a1')
            ->join('quizzes', 'a1.quiz_id', '=', 'quizzes.id')
            ->where('quizzes.category_id', $categoryId)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('attempts as a2')
                    ->whereRaw('a2.user_id = a1.user_id')
                    ->whereRaw('a2.id > a1.id');
            })
            ->count(DB::raw('DISTINCT a1.user_id'));

        return [
            'unique_users' => (int) ($metrics->unique_users ?? 0),
            'total_attempts' => (int) ($metrics->total_attempts ?? 0),
            'avg_score' => round($metrics->avg_score ?? 0, 2),
            'attempts_per_user' => round($metrics->attempts_per_user ?? 0, 2),
            'return_rate' => $metrics->unique_users > 0 ? round(($returnRate / $metrics->unique_users) * 100, 2) : 0,
            'quizzes_per_category' => $totalQuizzes,
        ];
    }

    public function getCategoryConversionFunnel(int $categoryId): array
    {
        $viewers = DB::table('page_views')
            ->where('page_type', 'category')
            ->where('page_id', $categoryId)
            ->count(DB::raw('DISTINCT user_id'));

        $clickers = DB::table('quizzes')
            ->join('attempts', 'quizzes.id', '=', 'attempts.quiz_id')
            ->where('quizzes.category_id', $categoryId)
            ->count(DB::raw('DISTINCT attempts.user_id'));

        $completers = DB::table('quizzes')
            ->join('attempts', 'quizzes.id', '=', 'attempts.quiz_id')
            ->where('quizzes.category_id', $categoryId)
            ->where('attempts.status', 'completed')
            ->count(DB::raw('DISTINCT attempts.user_id'));

        return [
            ['stage' => 'Viewed', 'users' => $viewers],
            ['stage' => 'Started Quiz', 'users' => $clickers],
            ['stage' => 'Completed Quiz', 'users' => $completers],
        ];
    }

    public function getCategorySeasonality(int $categoryId): array
    {
        return DB::table('attempts')
            ->join('quizzes', 'attempts.quiz_id', '=', 'quizzes.id')
            ->where('quizzes.category_id', $categoryId)
            ->where('attempts.status', 'completed')
            ->select(
                DB::raw('MONTH(attempts.created_at) as month'),
                DB::raw('COUNT(*) as attempts_count'),
                DB::raw('AVG(attempts.percentage_score) as avg_score')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    public function getCategoryForecast(int $categoryId, int $months = 3): array
    {
        $historicalData = $this->getCategoryPerformanceOverTime($categoryId, 90);
        
        $avgDailyAttempts = count($historicalData) > 0 
            ? array_sum(array_column($historicalData, 'attempts_count')) / count($historicalData)
            : 0;

        $forecast = [];
        for ($i = 1; $i <= $months; $i++) {
            $month = now()->addMonths($i);
            $forecast[] = [
                'month' => $month->format('Y-m'),
                'predicted_attempts' => round($avgDailyAttempts * 30 * (1 + ($i * 0.05))),
            ];
        }

        return $forecast;
    }

    public function getCategoryComparison(array $categoryIds): array
    {
        $comparison = [];
        
        foreach ($categoryIds as $categoryId) {
            $stats = $this->getCategoryStats($categoryId);
            $engagement = $this->getCategoryEngagementMetrics($categoryId);
            
            $comparison[] = [
                'category_id' => $categoryId,
                'category_name' => Category::find($categoryId)?->name,
                'stats' => $stats,
                'engagement' => $engagement,
            ];
        }

        return $comparison;
    }

    public function getCategoryAnomalies(int $categoryId): array
    {
        $metrics = $this->getCategoryPerformanceMetrics($categoryId);
        $anomalies = [];

        if ($metrics['avg_score'] < 40) {
            $anomalies[] = 'Very low average score';
        }

        if ($metrics['score_stddev'] > 30) {
            $anomalies[] = 'High score variance';
        }

        $dailyStats = $this->getCategoryPerformanceOverTime($categoryId, 7);
        if (count($dailyStats) > 0) {
            $lastDay = end($dailyStats);
            $avgLastWeek = array_sum(array_column($dailyStats, 'attempts_count')) / count($dailyStats);
            
            if ($lastDay['attempts_count'] > $avgLastWeek * 3) {
                $anomalies[] = 'Unusual spike in activity';
            }
            
            if ($lastDay['attempts_count'] < $avgLastWeek * 0.1) {
                $anomalies[] = 'Unusual drop in activity';
            }
        }

        return $anomalies;
    }

    public function getCategoryHealthScore(int $categoryId): float
    {
        $stats = $this->getCategoryStats($categoryId);
        $engagement = $this->getCategoryEngagementMetrics($categoryId);
        
        $score = 100;
        
        if ($stats['total_quizzes'] === 0) {
            $score -= 30;
        } elseif ($stats['total_quizzes'] < 5) {
            $score -= 15;
        }
        
        if ($engagement['unique_users'] < 10) {
            $score -= 20;
        }
        
        if ($engagement['avg_score'] < 50) {
            $score -= 20;
        } elseif ($engagement['avg_score'] < 70) {
            $score -= 10;
        }
        
        if ($engagement['return_rate'] < 20) {
            $score -= 15;
        }
        
        return max(0, min(100, $score));
    }

    public function getCategoryRecommendations(int $categoryId): array
    {
        $recommendations = [];
        $healthScore = $this->getCategoryHealthScore($categoryId);
        
        if ($healthScore < 50) {
            $stats = $this->getCategoryStats($categoryId);
            $engagement = $this->getCategoryEngagementMetrics($categoryId);
            
            if ($stats['total_quizzes'] < 5) {
                $recommendations[] = 'Add more quizzes to this category';
            }
            
            if ($engagement['unique_users'] < 10) {
                $recommendations[] = 'Promote this category to increase visibility';
            }
            
            if ($engagement['avg_score'] < 50) {
                $recommendations[] = 'Review quiz difficulty - average score is low';
            }
            
            if ($engagement['return_rate'] < 20) {
                $recommendations[] = 'Improve engagement to encourage return users';
            }
        }

        return $recommendations;
    }

    private function buildTree(array $categories, ?int $parentId = null): array
    {
        $tree = [];
        
        foreach ($categories as $category) {
            if ($category['parent_id'] == $parentId) {
                $children = $this->buildTree($categories, $category['id']);
                if (!empty($children)) {
                    $category['children'] = $children;
                }
                $tree[] = $category;
            }
        }
        
        return $tree;
    }

    private function calculateAvgDepth(): float
    {
        $categories = Category::all(['id', 'parent_id']);
        $depths = [];
        
        foreach ($categories as $category) {
            $depth = 0;
            $current = $category;
            
            while ($current->parent_id) {
                $depth++;
                $current = Category::find($current->parent_id);
                if (!$current) break;
            }
            
            $depths[] = $depth;
        }
        
        return count($depths) > 0 ? array_sum($depths) / count($depths) : 0;
    }

    private function calculateMaxDepth(): int
    {
        $maxDepth = 0;
        $categories = Category::all(['id', 'parent_id']);
        
        foreach ($categories as $category) {
            $depth = 0;
            $current = $category;
            
            while ($current->parent_id) {
                $depth++;
                $current = Category::find($current->parent_id);
                if (!$current) break;
            }
            
            $maxDepth = max($maxDepth, $depth);
        }
        
        return $maxDepth;
    }

    private function getCategoryIssues(Category $category): array
    {
        $issues = [];
        
        if (!$category->is_active) {
            $issues[] = 'Category is inactive';
        }
        
        $quizzesCount = $category->quizzes()->count();
        if ($quizzesCount === 0) {
            $issues[] = 'No quizzes in this category';
        } elseif ($quizzesCount < 3) {
            $issues[] = 'Less than 3 quizzes in this category';
        }
        
        $activeQuizzes = $category->quizzes()->where('is_published', true)->count();
        if ($activeQuizzes === 0) {
            $issues[] = 'No active quizzes in this category';
        }
        
        return $issues;
    }

    private function clearCache(?int $id = null, ?string $slug = null): void
    {
        if ($id) {
            Cache::forget("category.id.{$id}");
        }
        
        if ($slug) {
            Cache::forget("category.slug.{$slug}");
        }
        
        Cache::forget('categories.active');
        Cache::forget('categories.with_counts');
        Cache::forget('categories.popular');
    }
}