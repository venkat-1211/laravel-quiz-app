<?php

namespace App\Services;

use App\DTOs\CategoryDTO;
use App\Services\Interfaces\CategoryServiceInterface;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoryService implements CategoryServiceInterface
{
    public function __construct(
        private CategoryRepositoryInterface $categoryRepository
    ) {}

    /**
     * Get all categories with pagination.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllCategories(int $perPage = 15): LengthAwarePaginator
    {
        return $this->categoryRepository->getAll($perPage);
    }

    /**
     * Get all active categories.
     *
     * @return array
     */
    public function getAllActive(): array
    {
        return Cache::remember('categories.active', 3600, function () {
            $categories = $this->categoryRepository->getActive();
            return array_map(function ($category) {
                return CategoryDTO::fromArray($category);
            }, $categories);
        });
    }

    /**
     * Get category by ID.
     *
     * @param int $id
     * @return CategoryDTO|null
     */
    public function getCategoryById(int $id): ?CategoryDTO
    {
        $category = $this->categoryRepository->findById($id);
        
        if (!$category) {
            return null;
        }
        
        return CategoryDTO::fromModel($category);
    }

    /**
     * Get category by slug.
     *
     * @param string $slug
     * @return CategoryDTO|null
     */
    public function getCategoryBySlug(string $slug): ?CategoryDTO
    {
        $category = $this->categoryRepository->findBySlug($slug);
        
        if (!$category) {
            return null;
        }
        
        return CategoryDTO::fromModel($category);
    }

    /**
     * Create a new category.
     *
     * @param array $data
     * @return CategoryDTO
     */
    public function createCategory(array $data): CategoryDTO
    {
        DB::beginTransaction();
        
        try {
            $category = $this->categoryRepository->create($data);
            
            $this->clearCache();
            
            DB::commit();
            
            return CategoryDTO::fromModel($category);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to create category: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing category.
     *
     * @param int $id
     * @param array $data
     * @return CategoryDTO
     */
    public function updateCategory(int $id, array $data): CategoryDTO
    {
        DB::beginTransaction();
        
        try {
            $category = $this->categoryRepository->update($id, $data);
            
            $this->clearCache();
            
            DB::commit();
            
            return CategoryDTO::fromModel($category);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to update category: ' . $e->getMessage());
        }
    }

    /**
     * Delete a category.
     *
     * @param int $id
     * @return bool
     */
    public function deleteCategory(int $id): bool
    {
        DB::beginTransaction();
        
        try {
            $category = $this->categoryRepository->findById($id);
            
            if (!$category) {
                throw new \Exception('Category not found');
            }
            
            // Check if category has quizzes
            if ($category->quizzes()->count() > 0) {
                throw new \Exception('Cannot delete category with associated quizzes');
            }
            
            $result = $this->categoryRepository->delete($id);
            
            $this->clearCache();
            
            DB::commit();
            
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to delete category: ' . $e->getMessage());
        }
    }

    /**
     * Get categories with quiz counts.
     *
     * @return array
     */
    public function getCategoriesWithQuizCounts(): array
    {
        return Cache::remember('categories.with_counts', 3600, function () {
            return $this->categoryRepository->getWithQuizCounts();
        });
    }

    /**
     * Get popular categories based on quiz attempts.
     *
     * @param int $limit
     * @return array
     */
    public function getPopularCategories(int $limit = 5): array
    {
        return Cache::remember('categories.popular.' . $limit, 3600, function () use ($limit) {
            return $this->categoryRepository->getPopularCategories($limit);
        });
    }

    /**
     * Get category statistics.
     *
     * @param int $categoryId
     * @return array
     */
    public function getCategoryStats(int $categoryId): array
    {
        return Cache::remember("category.stats.{$categoryId}", 3600, function () use ($categoryId) {
            return $this->categoryRepository->getCategoryStats($categoryId);
        });
    }

    /**
     * Get categories tree structure.
     *
     * @return array
     */
    public function getCategoryTree(): array
    {
        return Cache::remember('categories.tree', 3600, function () {
            $categories = $this->categoryRepository->getAll(100);
            return $this->buildTree($categories->items());
        });
    }

    /**
     * Reorder categories.
     *
     * @param array $order
     * @return bool
     */
    public function reorderCategories(array $order): bool
    {
        DB::beginTransaction();
        
        try {
            $result = $this->categoryRepository->reorder($order);
            
            $this->clearCache();
            
            DB::commit();
            
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to reorder categories: ' . $e->getMessage());
        }
    }

    /**
     * Bulk update category status.
     *
     * @param array $categoryIds
     * @param bool $isActive
     * @return int
     */
    public function bulkUpdateStatus(array $categoryIds, bool $isActive): int
    {
        DB::beginTransaction();
        
        try {
            $count = $this->categoryRepository->bulkUpdateStatus($categoryIds, $isActive);
            
            $this->clearCache();
            
            DB::commit();
            
            return $count;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to update category statuses: ' . $e->getMessage());
        }
    }

    /**
     * Search categories.
     *
     * @param string $query
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchCategories(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->categoryRepository->search($query, $perPage);
    }

    /**
     * Get categories by parent ID.
     *
     * @param int|null $parentId
     * @return array
     */
    public function getCategoriesByParent(?int $parentId = null): array
    {
        return Cache::remember('categories.parent.' . ($parentId ?? 'root'), 3600, function () use ($parentId) {
            return $this->categoryRepository->getByParent($parentId);
        });
    }

    /**
     * Get category path from root to category.
     *
     * @param int $categoryId
     * @return array
     */
    public function getCategoryPath(int $categoryId): array
    {
        return Cache::remember("category.path.{$categoryId}", 3600, function () use ($categoryId) {
            $path = [];
            $category = $this->categoryRepository->findById($categoryId);
            
            while ($category) {
                array_unshift($path, [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ]);
                
                if ($category->parent_id) {
                    $category = $this->categoryRepository->findById($category->parent_id);
                } else {
                    $category = null;
                }
            }
            
            return $path;
        });
    }

    /**
     * Get category siblings.
     *
     * @param int $categoryId
     * @return array
     */
    public function getCategorySiblings(int $categoryId): array
    {
        return Cache::remember("category.siblings.{$categoryId}", 3600, function () use ($categoryId) {
            $category = $this->categoryRepository->findById($categoryId);
            
            if (!$category) {
                return [];
            }
            
            return $this->categoryRepository->getByParent($category->parent_id);
        });
    }

    /**
     * Get category children with quiz counts.
     *
     * @param int $categoryId
     * @return array
     */
    public function getCategoryChildren(int $categoryId): array
    {
        return Cache::remember("category.children.{$categoryId}", 3600, function () use ($categoryId) {
            return $this->categoryRepository->getChildrenWithQuizCounts($categoryId);
        });
    }

    /**
     * Merge categories.
     *
     * @param int $sourceCategoryId
     * @param int $targetCategoryId
     * @return bool
     */
    public function mergeCategories(int $sourceCategoryId, int $targetCategoryId): bool
    {
        DB::beginTransaction();
        
        try {
            // Move all quizzes from source to target
            DB::table('quizzes')
                ->where('category_id', $sourceCategoryId)
                ->update(['category_id' => $targetCategoryId]);
            
            // Delete source category
            $this->categoryRepository->delete($sourceCategoryId);
            
            $this->clearCache();
            
            DB::commit();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to merge categories: ' . $e->getMessage());
        }
    }

    /**
     * Export categories data.
     *
     * @param array $filters
     * @return array
     */
    public function exportCategories(array $filters = []): array
    {
        return $this->categoryRepository->exportForReporting($filters);
    }

    /**
     * Import categories from array.
     *
     * @param array $data
     * @return array
     */
    public function importCategories(array $data): array
    {
        $imported = 0;
        $failed = [];
        
        DB::beginTransaction();
        
        try {
            foreach ($data as $index => $categoryData) {
                try {
                    $this->validateCategoryData($categoryData);
                    
                    $this->categoryRepository->create([
                        'name' => $categoryData['name'],
                        'slug' => $categoryData['slug'] ?? null,
                        'description' => $categoryData['description'] ?? null,
                        'icon' => $categoryData['icon'] ?? null,
                        'is_active' => $categoryData['is_active'] ?? true,
                        'order' => $categoryData['order'] ?? 0,
                        'parent_id' => $categoryData['parent_id'] ?? null,
                    ]);
                    
                    $imported++;
                } catch (\Exception $e) {
                    $failed[] = [
                        'row' => $index + 1,
                        'data' => $categoryData,
                        'error' => $e->getMessage(),
                    ];
                }
            }
            
            $this->clearCache();
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to import categories: ' . $e->getMessage());
        }
        
        return [
            'imported' => $imported,
            'failed' => $failed,
        ];
    }

    /**
     * Validate category data.
     *
     * @param array $data
     * @return array
     */
    public function validateCategoryData(array $data): array
    {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors[] = 'Category name is required';
        }
        
        if (isset($data['parent_id']) && $data['parent_id']) {
            $parent = $this->categoryRepository->findById($data['parent_id']);
            if (!$parent) {
                $errors[] = 'Parent category not found';
            }
        }
        
        return $errors;
    }

    /**
     * Get category suggestions based on quiz title/description.
     *
     * @param string $text
     * @param int $limit
     * @return array
     */
    public function suggestCategories(string $text, int $limit = 3): array
    {
        $keywords = $this->extractKeywords($text);
        $suggestions = [];
        
        foreach ($keywords as $keyword) {
            $categories = $this->categoryRepository->search($keyword, $limit);
            foreach ($categories->items() as $category) {
                $suggestions[$category['id']] = [
                    'id' => $category['id'],
                    'name' => $category['name'],
                    'relevance' => $this->calculateRelevance($keyword, $category['name']),
                ];
            }
        }
        
        // Sort by relevance
        usort($suggestions, fn($a, $b) => $b['relevance'] <=> $a['relevance']);
        
        return array_slice($suggestions, 0, $limit);
    }

    /**
     * Get category performance over time.
     *
     * @param int $categoryId
     * @param int $days
     * @return array
     */
    public function getCategoryPerformance(int $categoryId, int $days = 30): array
    {
        return Cache::remember("category.performance.{$categoryId}.{$days}", 3600, function () use ($categoryId, $days) {
            $startDate = now()->subDays($days);
            
            $performance = DB::table('attempts')
                ->join('quizzes', 'attempts.quiz_id', '=', 'quizzes.id')
                ->where('quizzes.category_id', $categoryId)
                ->where('attempts.status', 'completed')
                ->where('attempts.created_at', '>=', $startDate)
                ->select(
                    DB::raw('DATE(attempts.created_at) as date'),
                    DB::raw('COUNT(*) as attempts'),
                    DB::raw('AVG(attempts.percentage_score) as avg_score')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->toArray();
            
            return $performance;
        });
    }

    /**
     * Get top categories by user engagement.
     *
     * @param int $limit
     * @return array
     */
    public function getTopCategoriesByEngagement(int $limit = 10): array
    {
        return Cache::remember('categories.top_engagement.' . $limit, 3600, function () use ($limit) {
            return DB::table('categories')
                ->select(
                    'categories.id',
                    'categories.name',
                    'categories.slug',
                    DB::raw('COUNT(DISTINCT attempts.user_id) as unique_users'),
                    DB::raw('COUNT(attempts.id) as total_attempts'),
                    DB::raw('AVG(attempts.percentage_score) as avg_score')
                )
                ->leftJoin('quizzes', 'categories.id', '=', 'quizzes.category_id')
                ->leftJoin('attempts', 'quizzes.id', '=', 'attempts.quiz_id')
                ->where('attempts.status', 'completed')
                ->groupBy('categories.id', 'categories.name', 'categories.slug')
                ->orderByDesc('unique_users')
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }

    /**
     * Archive old categories.
     *
     * @param int $daysUnused
     * @return int
     */
    public function archiveOldCategories(int $daysUnused = 180): int
    {
        $cutoffDate = now()->subDays($daysUnused);
        
        $categories = DB::table('categories')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('quizzes')
                    ->whereRaw('quizzes.category_id = categories.id');
            })
            ->where('created_at', '<', $cutoffDate)
            ->get();
        
        if ($categories->isEmpty()) {
            return 0;
        }
        
        DB::beginTransaction();
        
        try {
            foreach ($categories as $category) {
                DB::table('categories_archive')->insert([
                    'original_id' => $category->id,
                    'name' => $category->name,
                    'data' => json_encode((array) $category),
                    'archived_at' => now(),
                ]);
                
                DB::table('categories')->where('id', $category->id)->delete();
            }
            
            $this->clearCache();
            
            DB::commit();
            
            return $categories->count();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to archive categories: ' . $e->getMessage());
        }
    }

    /**
     * Restore archived categories.
     *
     * @param array $categoryIds
     * @return int
     */
    public function restoreArchivedCategories(array $categoryIds): int
    {
        $archivedCategories = DB::table('categories_archive')
            ->whereIn('original_id', $categoryIds)
            ->get();
        
        if ($archivedCategories->isEmpty()) {
            return 0;
        }
        
        DB::beginTransaction();
        
        try {
            foreach ($archivedCategories as $archived) {
                $data = json_decode($archived->data, true);
                unset($data['id']);
                
                DB::table('categories')->insert($data);
                DB::table('categories_archive')->where('id', $archived->id)->delete();
            }
            
            $this->clearCache();
            
            DB::commit();
            
            return $archivedCategories->count();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to restore categories: ' . $e->getMessage());
        }
    }

    /**
     * Get category health status.
     *
     * @param int $categoryId
     * @return array
     */
    public function getCategoryHealth(int $categoryId): array
    {
        $category = $this->categoryRepository->findById($categoryId);
        
        if (!$category) {
            return ['status' => 'not_found'];
        }
        
        $quizzesCount = $category->quizzes()->count();
        $activeQuizzes = $category->quizzes()->where('is_published', true)->count();
        $totalAttempts = DB::table('attempts')
            ->join('quizzes', 'attempts.quiz_id', '=', 'quizzes.id')
            ->where('quizzes.category_id', $categoryId)
            ->count();
        
        $healthScore = 100;
        
        if ($quizzesCount === 0) {
            $healthScore -= 30;
        }
        
        if ($activeQuizzes === 0) {
            $healthScore -= 30;
        }
        
        if ($totalAttempts < 10) {
            $healthScore -= 20;
        }
        
        return [
            'category_id' => $categoryId,
            'category_name' => $category->name,
            'health_score' => max(0, $healthScore),
            'quizzes_count' => $quizzesCount,
            'active_quizzes' => $activeQuizzes,
            'total_attempts' => $totalAttempts,
            'status' => $healthScore >= 70 ? 'healthy' : ($healthScore >= 40 ? 'warning' : 'critical'),
            'issues' => $this->getCategoryIssues($categoryId, $quizzesCount, $activeQuizzes, $totalAttempts),
        ];
    }

    /**
     * Get recommended categories for user.
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getRecommendedCategories(int $userId, int $limit = 5): array
    {
        $userCategories = DB::table('attempts')
            ->join('quizzes', 'attempts.quiz_id', '=', 'quizzes.id')
            ->where('attempts.user_id', $userId)
            ->where('attempts.status', 'completed')
            ->select('quizzes.category_id', DB::raw('COUNT(*) as attempt_count'))
            ->groupBy('quizzes.category_id')
            ->orderByDesc('attempt_count')
            ->limit(5)
            ->pluck('category_id')
            ->toArray();
        
        if (empty($userCategories)) {
            return $this->getPopularCategories($limit);
        }
        
        return DB::table('categories')
            ->whereIn('id', $userCategories)
            ->where('is_active', true)
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Clear category cache.
     *
     * @return void
     */
    private function clearCache(): void
    {
        Cache::forget('categories.active');
        Cache::forget('categories.tree');
        Cache::forget('categories.with_counts');
        Cache::forget('categories.popular');
        Cache::forget('categories.top_engagement');
        Cache::forgetPattern('category.stats.*');
        Cache::forgetPattern('category.children.*');
        Cache::forgetPattern('category.path.*');
        Cache::forgetPattern('category.siblings.*');
        Cache::forgetPattern('category.performance.*');
        Cache::forgetPattern('categories.parent.*');
    }

    /**
     * Build category tree from flat list.
     *
     * @param array $categories
     * @param int|null $parentId
     * @return array
     */
    private function buildTree(array $categories, ?int $parentId = null): array
    {
        $tree = [];
        
        foreach ($categories as $category) {
            if ($category['parent_id'] == $parentId) {
                $children = $this->buildTree($categories, $category['id']);
                $category['children'] = $children;
                $tree[] = $category;
            }
        }
        
        return $tree;
    }

    /**
     * Extract keywords from text.
     *
     * @param string $text
     * @return array
     */
    private function extractKeywords(string $text): array
    {
        $text = strtolower(preg_replace('/[^\w\s]/', '', $text));
        $words = explode(' ', $text);
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        
        $keywords = array_filter($words, function ($word) use ($stopWords) {
            return strlen($word) > 3 && !in_array($word, $stopWords);
        });
        
        return array_slice(array_values($keywords), 0, 5);
    }

    /**
     * Calculate relevance between keyword and category name.
     *
     * @param string $keyword
     * @param string $categoryName
     * @return float
     */
    private function calculateRelevance(string $keyword, string $categoryName): float
    {
        $keyword = strtolower($keyword);
        $categoryName = strtolower($categoryName);
        
        if (strpos($categoryName, $keyword) !== false) {
            return 1.0;
        }
        
        similar_text($keyword, $categoryName, $percent);
        return $percent / 100;
    }

    /**
     * Get category issues for health check.
     *
     * @param int $categoryId
     * @param int $quizzesCount
     * @param int $activeQuizzes
     * @param int $totalAttempts
     * @return array
     */
    private function getCategoryIssues(int $categoryId, int $quizzesCount, int $activeQuizzes, int $totalAttempts): array
    {
        $issues = [];
        
        if ($quizzesCount === 0) {
            $issues[] = 'No quizzes in this category';
        } elseif ($activeQuizzes === 0) {
            $issues[] = 'No active quizzes in this category';
        }
        
        if ($totalAttempts < 10) {
            $issues[] = 'Low user engagement';
        }
        
        return $issues;
    }
}