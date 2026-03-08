<?php

namespace App\Services\Interfaces;

use App\DTOs\UserDTO;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserServiceInterface
{
    public function getUserById(int $id): ?UserDTO;
    
    public function getUserByEmail(string $email): ?UserDTO;
    
    public function createUser(array $data): UserDTO;
    
    public function updateUser(int $id, array $data): UserDTO;
    
    public function deleteUser(int $id): bool;
    
    public function getAllUsers(int $perPage = 15): LengthAwarePaginator;
    
    public function getUsersByRole(string $role, int $perPage = 15): LengthAwarePaginator;
    
    public function searchUsers(string $query, int $perPage = 15): LengthAwarePaginator;
    
    public function updateProfile(int $id, array $data): UserDTO;
    
    public function changePassword(int $id, string $newPassword): bool;
    
    public function verifyPassword(int $id, string $password): bool;
    
    public function updateLastLogin(int $id, string $ip): bool;
    
    public function toggleActive(int $id): bool;
    
    public function getUserStats(int $userId): array;
    
    public function getUserAchievements(int $userId): array;
    
    public function getActiveUsersCount(int $days = 7): int;
    
    public function getNewUsersCount(string $period = 'today'): int;
    
    public function getUsersGrowth(int $months = 6): array;
    
    public function exportUsers(array $filters = []): array;
    
    public function bulkUpdateStatus(array $userIds, bool $isActive): int;
    
    public function getUsersByDateRange(string $startDate, string $endDate): array;
    
    public function getTopUsersByPoints(int $limit = 10): array;
    
    public function getRetentionStats(): array;
    
    public function getDemographics(): array;
    
    public function getActivityHeatmap(int $userId, int $days = 30): array;
    
    public function getUsersNeedingVerification(): array;
    
    public function verifyEmail(int $userId): bool;
    
    public function sendPasswordResetLink(string $email): bool;
    
    public function getRecentActivity(int $userId, int $limit = 10): array;
    
    public function getPerformanceTrend(int $userId, int $limit = 10): array;
}