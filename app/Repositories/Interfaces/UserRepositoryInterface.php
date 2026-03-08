<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;
    
    public function findByEmail(string $email): ?User;
    
    public function create(array $data): User;
    
    public function update(int $id, array $data): User;
    
    public function delete(int $id): bool;
    
    public function getAll(int $perPage = 15): LengthAwarePaginator;
    
    public function getByRole(string $role, int $perPage = 15): LengthAwarePaginator;
    
    public function search(string $query, int $perPage = 15): LengthAwarePaginator;
    
    public function getByDateRange(string $startDate, string $endDate): array;
    
    public function getActiveUsers(int $days = 7): array;
    
    public function getInactiveUsers(): array;
    
    public function getUsersWithMostAttempts(int $limit = 10): array;
    
    public function getUsersWithHighestScore(int $limit = 10): array;
    
    public function getUsersBySocialType(string $type): array;
    
    public function getUsersNeedingVerification(): array;
    
    public function bulkUpdateStatus(array $userIds, bool $isActive): int;
    
    public function getUserWithRelations(int $id): ?User;
    
    public function getUserStats(int $userId): array;
    
    public function countNewUsers(string $period = 'today'): int;
    
    public function getUsersGrowth(int $months = 6): array;
    
    public function getDemographics(): array;
}