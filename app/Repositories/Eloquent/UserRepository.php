<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?User
    {
        return Cache::remember("user.id.{$id}", 3600, function () use ($id) {
            return User::with(['roles', 'leaderboard', 'achievements'])->find($id);
        });
    }

    public function findByEmail(string $email): ?User
    {
        return User::with(['roles', 'leaderboard', 'achievements'])
            ->where('email', $email)
            ->first();
    }

    public function create(array $data): User
    {
        $user = User::create($data);
        $this->clearCache($user->id);
        return $user;
    }

    public function update(int $id, array $data): User
    {
        $user = $this->findById($id);
        if (!$user) {
            throw new \Exception("User not found");
        }
        $user->update($data);
        $this->clearCache($id);
        return $user;
    }

    public function delete(int $id): bool
    {
        $user = $this->findById($id);
        if (!$user) {
            return false;
        }
        $result = $user->delete();
        $this->clearCache($id);
        return $result;
    }

    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        return User::with(['roles', 'leaderboard'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getByRole(string $role, int $perPage = 15): LengthAwarePaginator
    {
        return User::whereHas('roles', function ($query) use ($role) {
                $query->where('name', $role);
            })
            ->with(['roles', 'leaderboard'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return User::where('name', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->with(['roles', 'leaderboard'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getByDateRange(string $startDate, string $endDate): array
    {
        return User::whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at')
            ->get()
            ->toArray();
    }

    public function getActiveUsers(int $days = 7): array
    {
        return User::whereHas('attempts', function ($query) use ($days) {
                $query->where('created_at', '>=', now()->subDays($days));
            })
            ->withCount(['attempts' => function ($query) use ($days) {
                $query->where('created_at', '>=', now()->subDays($days));
            }])
            ->orderByDesc('attempts_count')
            ->limit(20)
            ->get()
            ->toArray();
    }

    public function getInactiveUsers(): array
    {
        return User::whereDoesntHave('attempts')
            ->orWhereDoesntHave('attempts', function ($query) {
                $query->where('created_at', '>=', now()->subDays(30));
            })
            ->limit(50)
            ->get()
            ->toArray();
    }

    public function getUsersWithMostAttempts(int $limit = 10): array
    {
        return User::withCount('attempts')
            ->orderByDesc('attempts_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getUsersWithHighestScore(int $limit = 10): array
    {
        return User::select('users.*')
            ->join('leaderboards', 'users.id', '=', 'leaderboards.user_id')
            ->orderByDesc('leaderboards.total_points')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getUsersBySocialType(string $type): array
    {
        return User::where('social_type', $type)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    public function getUsersNeedingVerification(): array
    {
        return User::whereNull('email_verified_at')
            ->where('created_at', '<=', now()->subDays(7))
            ->limit(50)
            ->get()
            ->toArray();
    }

    public function bulkUpdateStatus(array $userIds, bool $isActive): int
    {
        return User::whereIn('id', $userIds)
            ->update(['is_active' => $isActive]);
    }

    public function getUserWithRelations(int $id): ?User
    {
        return User::with(['roles', 'leaderboard', 'achievements', 'attempts.quiz'])
            ->find($id);
    }

    public function getUserStats(int $userId): array
    {
        $user = $this->findById($userId);
        
        if (!$user) {
            return [];
        }
        
        $stats = DB::table('attempts')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->select(
                DB::raw('COUNT(*) as total_attempts'),
                DB::raw('AVG(percentage_score) as avg_score'),
                DB::raw('SUM(score) as total_points'),
                DB::raw('COUNT(DISTINCT quiz_id) as unique_quizzes'),
                DB::raw('MAX(percentage_score) as highest_score'),
                DB::raw('MIN(percentage_score) as lowest_score')
            )
            ->first();
        
        return [
            'total_attempts' => (int) ($stats->total_attempts ?? 0),
            'avg_score' => round($stats->avg_score ?? 0, 2),
            'total_points' => (int) ($stats->total_points ?? 0),
            'unique_quizzes' => (int) ($stats->unique_quizzes ?? 0),
            'highest_score' => round($stats->highest_score ?? 0, 2),
            'lowest_score' => round($stats->lowest_score ?? 0, 2),
        ];
    }

    public function countNewUsers(string $period = 'today'): int
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

    public function getUsersGrowth(int $months = 6): array
    {
        $growth = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $count = User::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
            
            $growth[] = [
                'month' => $month->format('Y-m'),
                'count' => $count,
            ];
        }
        
        return $growth;
    }

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
        ];
    }

    private function clearCache(int $userId): void
    {
        Cache::forget("user.id.{$userId}");
        Cache::forget("user.stats.{$userId}");
    }
}