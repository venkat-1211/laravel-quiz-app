<?php

namespace App\Services;

use App\DTOs\UserDTO;
use App\Models\User;
use App\Services\Interfaces\UserServiceInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService implements UserServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    /**
     * Get user by ID.
     *
     * @param int $id
     * @return UserDTO|null
     */
    public function getUserById(int $id): ?UserDTO
    {
        $user = $this->userRepository->findById($id);
        
        if (!$user) {
            return null;
        }
        
        return UserDTO::fromModel($user);
    }

    /**
     * Get user by email.
     *
     * @param string $email
     * @return UserDTO|null
     */
    public function getUserByEmail(string $email): ?UserDTO
    {
        $user = $this->userRepository->findByEmail($email);
        
        if (!$user) {
            return null;
        }
        
        return UserDTO::fromModel($user);
    }

    /**
     * Create a new user.
     *
     * @param array $data
     * @return UserDTO
     */
    public function createUser(array $data): UserDTO
    {
        DB::beginTransaction();
        
        try {
            // Hash password if provided
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }
            
            $user = $this->userRepository->create($data);
            
            // Assign default role
            $user->addRole('user');
            
            // Create leaderboard entry
            \App\Models\Leaderboard::create([
                'user_id' => $user->id,
                'total_points' => 0,
                'quizzes_completed' => 0,
                'total_attempts' => 0,
                'average_score' => 0,
                'rank' => 0,
                'weekly_rank' => 0,
                'monthly_rank' => 0,
            ]);
            
            DB::commit();
            
            return UserDTO::fromModel($user);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing user.
     *
     * @param int $id
     * @param array $data
     * @return UserDTO
     */
    public function updateUser(int $id, array $data): UserDTO
    {
        DB::beginTransaction();
        
        try {
            // Hash password if provided
            if (isset($data['password']) && !empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }
            
            $user = $this->userRepository->update($id, $data);
            
            DB::commit();
            
            return UserDTO::fromModel($user);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to update user: ' . $e->getMessage());
        }
    }

    /**
     * Delete a user.
     *
     * @param int $id
     * @return bool
     */
    public function deleteUser(int $id): bool
    {
        DB::beginTransaction();
        
        try {
            $result = $this->userRepository->delete($id);
            
            DB::commit();
            
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to delete user: ' . $e->getMessage());
        }
    }

    /**
     * Get all users with pagination.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllUsers(int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->getAll($perPage);
    }

    /**
     * Get users by role.
     *
     * @param string $role
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUsersByRole(string $role, int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->getByRole($role, $perPage);
    }

    /**
     * Search users.
     *
     * @param string $query
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchUsers(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->search($query, $perPage);
    }

    /**
     * Update user profile.
     *
     * @param int $id
     * @param array $data
     * @return UserDTO
     */
    public function updateProfile(int $id, array $data): UserDTO
    {
        DB::beginTransaction();
        
        try {
            // Don't allow email change through profile update
            unset($data['email']);
            unset($data['password']);
            
            $user = $this->userRepository->update($id, $data);
            
            DB::commit();
            
            return UserDTO::fromModel($user);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to update profile: ' . $e->getMessage());
        }
    }

    /**
     * Change user password.
     *
     * @param int $id
     * @param string $newPassword
     * @return bool
     */
    public function changePassword(int $id, string $newPassword): bool
    {
        DB::beginTransaction();
        
        try {
            $result = $this->userRepository->update($id, [
                'password' => Hash::make($newPassword)
            ]);
            
            DB::commit();
            
            return (bool) $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to change password: ' . $e->getMessage());
        }
    }

    /**
     * Verify user password.
     *
     * @param int $id
     * @param string $password
     * @return bool
     */
    public function verifyPassword(int $id, string $password): bool
    {
        $user = $this->userRepository->findById($id);
        
        if (!$user) {
            return false;
        }
        
        return Hash::check($password, $user->password);
    }

    /**
     * Update last login info.
     *
     * @param int $id
     * @param string $ip
     * @return bool
     */
    public function updateLastLogin(int $id, string $ip): bool
    {
        try {
            $this->userRepository->update($id, [
                'last_login_at' => now(),
                'last_login_ip' => $ip
            ]);
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Toggle user active status.
     *
     * @param int $id
     * @return bool
     */
    public function toggleActive(int $id): bool
    {
        DB::beginTransaction();
        
        try {
            $user = $this->userRepository->findById($id);
            
            if (!$user) {
                throw new \Exception('User not found');
            }
            
            $this->userRepository->update($id, [
                'is_active' => !$user->is_active
            ]);
            
            DB::commit();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to toggle user status: ' . $e->getMessage());
        }
    }

    /**
     * Get user statistics.
     *
     * @param int $userId
     * @return array
     */
    public function getUserStats(int $userId): array
    {
        return Cache::remember("user.stats.{$userId}", 3600, function () use ($userId) {
            $user = $this->userRepository->findById($userId);
            
            if (!$user) {
                return [];
            }
            
            $totalAttempts = $user->attempts()->count();
            $completedAttempts = $user->attempts()->where('status', 'completed')->count();
            $averageScore = $user->attempts()->where('status', 'completed')->avg('percentage_score') ?? 0;
            $totalPoints = $user->leaderboard->total_points ?? 0;
            
            $categoryStats = DB::table('attempts')
                ->join('quizzes', 'attempts.quiz_id', '=', 'quizzes.id')
                ->join('categories', 'quizzes.category_id', '=', 'categories.id')
                ->where('attempts.user_id', $userId)
                ->where('attempts.status', 'completed')
                ->select(
                    'categories.name',
                    DB::raw('COUNT(*) as attempts'),
                    DB::raw('AVG(attempts.percentage_score) as avg_score')
                )
                ->groupBy('categories.id', 'categories.name')
                ->orderByDesc('attempts')
                ->limit(5)
                ->get()
                ->toArray();
            
            $monthlyActivity = DB::table('attempts')
                ->where('user_id', $userId)
                ->where('status', 'completed')
                ->where('created_at', '>=', now()->subMonths(6))
                ->select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                    DB::raw('COUNT(*) as attempts'),
                    DB::raw('AVG(percentage_score) as avg_score')
                )
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->toArray();
            
            return [
                'total_attempts' => $totalAttempts,
                'completed_attempts' => $completedAttempts,
                'average_score' => round($averageScore, 2),
                'total_points' => $totalPoints,
                'quizzes_passed' => $user->attempts()
                    ->where('status', 'completed')
                    ->whereRaw('percentage_score >= quizzes.passing_score')
                    ->join('quizzes', 'attempts.quiz_id', '=', 'quizzes.id')
                    ->count(),
                'category_performance' => $categoryStats,
                'monthly_activity' => $monthlyActivity,
            ];
        });
    }

    /**
     * Get user achievements.
     *
     * @param int $userId
     * @return array
     */
    public function getUserAchievements(int $userId): array
    {
        $user = $this->userRepository->findById($userId);
        
        if (!$user) {
            return [];
        }
        
        return $user->achievements()
            ->withPivot('earned_at')
            ->orderBy('user_achievements.earned_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get active users count.
     *
     * @param int $days
     * @return int
     */
    public function getActiveUsersCount(int $days = 7): int
    {
        return User::whereHas('attempts', function ($query) use ($days) {
            $query->where('created_at', '>=', now()->subDays($days));
        })->count();
    }

    /**
     * Get new users count.
     *
     * @param string $period (today, week, month)
     * @return int
     */
    public function getNewUsersCount(string $period = 'today'): int
    {
        $query = User::query();
        
        switch ($period) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'week':
                $query->where('created_at', '>=', now()->startOfWeek());
                break;
            case 'month':
                $query->where('created_at', '>=', now()->startOfMonth());
                break;
        }
        
        return $query->count();
    }

    /**
     * Get users growth chart data.
     *
     * @param int $months
     * @return array
     */
    public function getUsersGrowth(int $months = 6): array
    {
        $data = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $count = User::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
            
            $data[] = [
                'month' => $month->format('M Y'),
                'count' => $count,
            ];
        }
        
        return $data;
    }

    /**
     * Export users data.
     *
     * @param array $filters
     * @return array
     */
    public function exportUsers(array $filters = []): array
    {
        $query = User::withCount('attempts')
            ->with(['leaderboard']);
        
        if (isset($filters['role'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('name', $filters['role']);
            });
        }
        
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        
        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }
        
        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }
        
        return $query->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->roles->first()->name ?? 'user',
                    'is_active' => $user->is_active ? 'Yes' : 'No',
                    'email_verified' => $user->email_verified_at ? 'Yes' : 'No',
                    'social_type' => $user->social_type ?? 'standard',
                    'total_attempts' => $user->attempts_count,
                    'total_points' => $user->leaderboard->total_points ?? 0,
                    'quizzes_completed' => $user->leaderboard->quizzes_completed ?? 0,
                    'average_score' => round($user->leaderboard->average_score ?? 0, 2),
                    'rank' => $user->leaderboard->rank ?? 0,
                    'joined_at' => $user->created_at->toDateTimeString(),
                    'last_login' => $user->last_login_at?->toDateTimeString(),
                    'last_login_ip' => $user->last_login_ip,
                ];
            })
            ->toArray();
    }

    /**
     * Bulk update users status.
     *
     * @param array $userIds
     * @param bool $isActive
     * @return int
     */
    public function bulkUpdateStatus(array $userIds, bool $isActive): int
    {
        return User::whereIn('id', $userIds)
            ->update(['is_active' => $isActive]);
    }

    /**
     * Get users by date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getUsersByDateRange(string $startDate, string $endDate): array
    {
        return User::whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at')
            ->get()
            ->toArray();
    }

    /**
     * Get top users by points.
     *
     * @param int $limit
     * @return array
     */
    public function getTopUsersByPoints(int $limit = 10): array
    {
        return User::with('leaderboard')
            ->whereHas('leaderboard')
            ->orderByDesc(
                \App\Models\Leaderboard::select('total_points')
                    ->whereColumn('user_id', 'users.id')
            )
            ->limit($limit)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'total_points' => $user->leaderboard->total_points ?? 0,
                    'quizzes_completed' => $user->leaderboard->quizzes_completed ?? 0,
                    'average_score' => round($user->leaderboard->average_score ?? 0, 2),
                ];
            })
            ->toArray();
    }

    /**
     * Get user retention stats.
     *
     * @return array
     */
    public function getRetentionStats(): array
    {
        $cohorts = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $usersInCohort = User::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
            
            $retention = [];
            for ($j = 0; $j <= 5; $j++) {
                $retentionMonth = $month->copy()->addMonths($j);
                $activeUsers = User::whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->whereHas('attempts', function ($q) use ($retentionMonth) {
                        $q->whereYear('created_at', $retentionMonth->year)
                            ->whereMonth('created_at', $retentionMonth->month);
                    })
                    ->count();
                
                $retention[] = $usersInCohort > 0 
                    ? round(($activeUsers / $usersInCohort) * 100, 2) 
                    : 0;
            }
            
            $cohorts[] = [
                'cohort' => $month->format('M Y'),
                'users' => $usersInCohort,
                'retention' => $retention,
            ];
        }
        
        return $cohorts;
    }

    /**
     * Get user demographics.
     *
     * @return array
     */
    public function getDemographics(): array
    {
        return [
            'by_country' => User::select('country', DB::raw('COUNT(*) as count'))
                ->whereNotNull('country')
                ->groupBy('country')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->toArray(),
            'by_social_type' => User::select('social_type', DB::raw('COUNT(*) as count'))
                ->groupBy('social_type')
                ->get()
                ->toArray(),
            'by_role' => DB::table('role_user')
                ->join('roles', 'role_user.role_id', '=', 'roles.id')
                ->select('roles.name', DB::raw('COUNT(*) as count'))
                ->groupBy('roles.id', 'roles.name')
                ->get()
                ->toArray(),
        ];
    }

    /**
     * Get user activity heatmap.
     *
     * @param int $userId
     * @param int $days
     * @return array
     */
    public function getActivityHeatmap(int $userId, int $days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();
        
        $activity = DB::table('attempts')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->toArray();
        
        $heatmap = [];
        $currentDate = clone $startDate;
        
        while ($currentDate <= now()) {
            $dateStr = $currentDate->format('Y-m-d');
            $heatmap[] = [
                'date' => $dateStr,
                'count' => isset($activity[$dateStr]) ? $activity[$dateStr]->count : 0,
            ];
            $currentDate->addDay();
        }
        
        return $heatmap;
    }

    /**
     * Get users needing verification.
     *
     * @return array
     */
    public function getUsersNeedingVerification(): array
    {
        return User::whereNull('email_verified_at')
            ->where('created_at', '<=', now()->subDays(7))
            ->limit(50)
            ->get()
            ->toArray();
    }

    /**
     * Verify user email.
     *
     * @param int $userId
     * @return bool
     */
    public function verifyEmail(int $userId): bool
    {
        return (bool) $this->userRepository->update($userId, [
            'email_verified_at' => now()
        ]);
    }

    /**
     * Send password reset link.
     *
     * @param string $email
     * @return bool
     */
    public function sendPasswordResetLink(string $email): bool
    {
        // This will be handled by Laravel's built-in password reset
        // Just return true as it's handled by Fortify
        return true;
    }

    /**
     * Get user's recent activity.
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getRecentActivity(int $userId, int $limit = 10): array
    {
        return DB::table('attempts')
            ->join('quizzes', 'attempts.quiz_id', '=', 'quizzes.id')
            ->where('attempts.user_id', $userId)
            ->where('attempts.status', 'completed')
            ->orderBy('attempts.created_at', 'desc')
            ->limit($limit)
            ->select(
                'attempts.id',
                'quizzes.title as quiz_title',
                'quizzes.slug as quiz_slug',
                'attempts.percentage_score as score',
                'attempts.time_taken',
                'attempts.created_at'
            )
            ->get()
            ->toArray();
    }

    /**
     * Get user's performance trend.
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getPerformanceTrend(int $userId, int $limit = 10): array
    {
        return DB::table('attempts')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->select(
                DB::raw('DATE(created_at) as date'),
                'percentage_score as score'
            )
            ->get()
            ->toArray();
    }
}