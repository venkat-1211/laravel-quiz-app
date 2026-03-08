<?php

namespace App\Repositories\Interfaces;

use App\Models\Category;
use Illuminate\Pagination\LengthAwarePaginator;

interface CategoryRepositoryInterface
{
    public function findById(int $id): ?Category;
    
    public function findBySlug(string $slug): ?Category;
    
    public function create(array $data): Category;
    
    public function update(int $id, array $data): Category;
    
    public function delete(int $id): bool;
    
    public function getAll(int $perPage = 15): LengthAwarePaginator;
    
    public function getActive(): array;
    
    public function getWithQuizCounts(): array;
    
    public function getPopularCategories(int $limit = 5): array;
    
    public function getCategoryStats(int $categoryId): array;
    
    public function search(string $query, int $perPage = 15): LengthAwarePaginator;
    
    public function getByParent(?int $parentId = null): array;
    
    public function reorder(array $order): bool;
    
    public function bulkUpdateStatus(array $categoryIds, bool $isActive): int;
    
    public function getChildrenWithQuizCounts(int $parentId): array;
    
    public function getCategoryHierarchy(): array;
    
    public function getCategoriesWithRecentActivity(int $days = 7): array;
    
    public function getCategoriesWithLowActivity(int $threshold = 10): array;
    
    public function getCategoryPerformanceOverTime(int $categoryId, int $days = 30): array;
    
    public function getCategoryLeaderboard(int $categoryId, int $limit = 10): array;
    
    public function getSimilarCategories(int $categoryId, int $limit = 5): array;
    
    public function exportForReporting(array $filters = []): array;
    
    public function getCategoryUsageStats(): array;
    
    public function getCategoryGrowth(int $months = 6): array;
    
    public function getCategoryHierarchyStats(): array;
    
    public function getCategoriesNeedingReview(): array;
    
    public function getCategorySuggestions(string $input, int $limit = 5): array;
    
    public function getRelatedCategories(int $categoryId, int $limit = 5): array;
    
    public function getCategoryPerformanceMetrics(int $categoryId): array;
    
    public function getCategoryEngagementMetrics(int $categoryId): array;
    
    public function getCategoryConversionFunnel(int $categoryId): array;
    
    public function getCategorySeasonality(int $categoryId): array;
    
    public function getCategoryForecast(int $categoryId, int $months = 3): array;
    
    public function getCategoryComparison(array $categoryIds): array;
    
    public function getCategoryAnomalies(int $categoryId): array;
    
    public function getCategoryHealthScore(int $categoryId): float;
    
    public function getCategoryRecommendations(int $categoryId): array;
}