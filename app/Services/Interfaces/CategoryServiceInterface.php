<?php

namespace App\Services\Interfaces;

use App\DTOs\CategoryDTO;
use Illuminate\Pagination\LengthAwarePaginator;

interface CategoryServiceInterface
{
    public function getAllCategories(int $perPage = 15): LengthAwarePaginator;
    
    public function getAllActive(): array;
    
    public function getCategoryById(int $id): ?CategoryDTO;
    
    public function getCategoryBySlug(string $slug): ?CategoryDTO;
    
    public function createCategory(array $data): CategoryDTO;
    
    public function updateCategory(int $id, array $data): CategoryDTO;
    
    public function deleteCategory(int $id): bool;
    
    public function getCategoriesWithQuizCounts(): array;
    
    public function getPopularCategories(int $limit = 5): array;
    
    public function getCategoryStats(int $categoryId): array;
    
    public function getCategoryTree(): array;
    
    public function reorderCategories(array $order): bool;
    
    public function bulkUpdateStatus(array $categoryIds, bool $isActive): int;
    
    public function searchCategories(string $query, int $perPage = 15): LengthAwarePaginator;
    
    public function getCategoriesByParent(?int $parentId = null): array;
    
    public function getCategoryPath(int $categoryId): array;
    
    public function getCategorySiblings(int $categoryId): array;
    
    public function getCategoryChildren(int $categoryId): array;
    
    public function mergeCategories(int $sourceCategoryId, int $targetCategoryId): bool;
    
    public function exportCategories(array $filters = []): array;
    
    public function importCategories(array $data): array;
    
    public function validateCategoryData(array $data): array;
    
    public function suggestCategories(string $text, int $limit = 3): array;
    
    public function getCategoryPerformance(int $categoryId, int $days = 30): array;
    
    public function getTopCategoriesByEngagement(int $limit = 10): array;
    
    public function archiveOldCategories(int $daysUnused = 180): int;
    
    public function restoreArchivedCategories(array $categoryIds): int;
    
    public function getCategoryHealth(int $categoryId): array;
    
    public function getRecommendedCategories(int $userId, int $limit = 5): array;
}