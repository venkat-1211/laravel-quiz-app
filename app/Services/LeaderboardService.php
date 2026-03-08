<?php

namespace App\Services;

use App\Models\Leaderboard;
use App\Models\Attempt;
use App\Models\User;
use App\Services\Interfaces\LeaderboardServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LeaderboardService implements LeaderboardServiceInterface
{
    public function getGlobalLeaderboard(int $limit = 50): array
    {
        return Cache::remember('leaderboard.global', 300, function () use ($limit) {
            return Leaderboard::with('user')
                ->orderBy('rank')
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    return [
                        'rank' => $item->rank,
                        'user' => [
                            'id' => $item->user->id,
                            'name' => $item->user->name,
                            'avatar' => $item->user->avatar,
                        ],
                        'total_points' => $item->total_points,
                        'quizzes_completed' => $item->quizzes_completed,
                        'average_score' => round($item->average_score, 2),
                        'badges' => $item->badges ?? [],
                    ];
                })
                ->toArray();
        });
    }

    public function getWeeklyLeaderboard(int $limit = 50): array
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        return Cache::remember('leaderboard.weekly.' . now()->weekOfYear, 300, function () use ($limit, $startOfWeek, $endOfWeek) {
            $data = DB::table('attempts')
                ->select(
                    'users.id',
                    'users.name',
                    'users.avatar',
                    DB::raw('COUNT(DISTINCT attempts.id) as quizzes_completed'),
                    DB::raw('SUM(attempts.score) as weekly_points'),
                    DB::raw('AVG(attempts.percentage_score) as avg_score')
                )
                ->join('users', 'users.id', '=', 'attempts.user_id')
                ->where('attempts.status', 'completed')
                ->whereBetween('attempts.completed_at', [$startOfWeek, $endOfWeek])
                ->groupBy('users.id', 'users.name', 'users.avatar')
                ->orderBy('weekly_points', 'desc')
                ->limit($limit)
                ->get();

            $rank = 1;
            return $data->map(function ($item) use (&$rank) {
                return [
                    'rank' => $rank++,
                    'user' => [
                        'id' => $item->id,
                        'name' => $item->name,
                        'avatar' => $item->avatar,
                    ],
                    'weekly_points' => $item->weekly_points,
                    'quizzes_completed' => $item->quizzes_completed,
                    'average_score' => round($item->avg_score, 2),
                ];
            })->toArray();
        });
    }

    public function getMonthlyLeaderboard(int $limit = 50): array
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        return Cache::remember('leaderboard.monthly.' . now()->month, 300, function () use ($limit, $startOfMonth, $endOfMonth) {
            $data = DB::table('attempts')
                ->select(
                    'users.id',
                    'users.name',
                    'users.avatar',
                    DB::raw('COUNT(DISTINCT attempts.id) as quizzes_completed'),
                    DB::raw('SUM(attempts.score) as monthly_points'),
                    DB::raw('AVG(attempts.percentage_score) as avg_score')
                )
                ->join('users', 'users.id', '=', 'attempts.user_id')
                ->where('attempts.status', 'completed')
                ->whereBetween('attempts.completed_at', [$startOfMonth, $endOfMonth])
                ->groupBy('users.id', 'users.name', 'users.avatar')
                ->orderBy('monthly_points', 'desc')
                ->limit($limit)
                ->get();

            $rank = 1;
            return $data->map(function ($item) use (&$rank) {
                return [
                    'rank' => $rank++,
                    'user' => [
                        'id' => $item->id,
                        'name' => $item->name,
                        'avatar' => $item->avatar,
                    ],
                    'monthly_points' => $item->monthly_points,
                    'quizzes_completed' => $item->quizzes_completed,
                    'average_score' => round($item->avg_score, 2),
                ];
            })->toArray();
        });
    }

    public function getUserRank(int $userId, string $type = 'global'): ?array
    {
        switch ($type) {
            case 'weekly':
                return $this->getUserWeeklyRank($userId);
            case 'monthly':
                return $this->getUserMonthlyRank($userId);
            default:
                return $this->getUserGlobalRank($userId);
        }
    }

    private function getUserGlobalRank(int $userId): ?array
    {
        $leaderboard = Leaderboard::where('user_id', $userId)->first();
        
        if (!$leaderboard) {
            return null;
        }

        return [
            'rank' => $leaderboard->rank,
            'total_points' => $leaderboard->total_points,
            'quizzes_completed' => $leaderboard->quizzes_completed,
            'average_score' => $leaderboard->average_score,
        ];
    }

    private function getUserWeeklyRank(int $userId): ?array
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        $data = DB::table('attempts')
            ->select(
                DB::raw('SUM(score) as weekly_points'),
                DB::raw('COUNT(DISTINCT attempts.id) as quizzes_completed'),
                DB::raw('AVG(percentage_score) as avg_score')
            )
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$startOfWeek, $endOfWeek])
            ->first();

        if (!$data || !$data->weekly_points) {
            return null;
        }

        $rank = DB::table('attempts')
            ->select('user_id')
            ->selectRaw('SUM(score) as total')
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$startOfWeek, $endOfWeek])
            ->groupBy('user_id')
            ->having('total', '>', $data->weekly_points)
            ->count() + 1;

        return [
            'rank' => $rank,
            'weekly_points' => $data->weekly_points,
            'quizzes_completed' => $data->quizzes_completed,
            'average_score' => round($data->avg_score, 2),
        ];
    }

    private function getUserMonthlyRank(int $userId): ?array
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $data = DB::table('attempts')
            ->select(
                DB::raw('SUM(score) as monthly_points'),
                DB::raw('COUNT(DISTINCT attempts.id) as quizzes_completed'),
                DB::raw('AVG(percentage_score) as avg_score')
            )
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$startOfMonth, $endOfMonth])
            ->first();

        if (!$data || !$data->monthly_points) {
            return null;
        }

        $rank = DB::table('attempts')
            ->select('user_id')
            ->selectRaw('SUM(score) as total')
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$startOfMonth, $endOfMonth])
            ->groupBy('user_id')
            ->having('total', '>', $data->monthly_points)
            ->count() + 1;

        return [
            'rank' => $rank,
            'monthly_points' => $data->monthly_points,
            'quizzes_completed' => $data->quizzes_completed,
            'average_score' => round($data->avg_score, 2),
        ];
    }

    public function updateAllRanks(): void
    {
        $leaderboards = Leaderboard::orderBy('total_points', 'desc')->get();
        
        foreach ($leaderboards as $index => $leaderboard) {
            $leaderboard->rank = $index + 1;
            $leaderboard->saveQuietly();
        }

        Cache::forget('leaderboard.global');
        Cache::forget('leaderboard.weekly.*');
        Cache::forget('leaderboard.monthly.*');
    }

    public function updateUserRank(int $userId): void
    {
        $leaderboard = Leaderboard::firstOrCreate(
            ['user_id' => $userId],
            [
                'total_points' => 0,
                'quizzes_completed' => 0,
                'total_attempts' => 0,
                'average_score' => 0,
            ]
        );
        
        $stats = Attempt::where('user_id', $userId)
            ->where('status', 'completed')
            ->select(
                DB::raw('COUNT(*) as quizzes_completed'),
                DB::raw('SUM(score) as total_points'),
                DB::raw('AVG(percentage_score) as avg_score'),
                DB::raw('COUNT(*) as total_attempts')
            )
            ->first();
        
        $leaderboard->quizzes_completed = $stats->quizzes_completed ?? 0;
        $leaderboard->total_points = $stats->total_points ?? 0;
        $leaderboard->average_score = $stats->avg_score ?? 0;
        $leaderboard->total_attempts = $stats->total_attempts ?? 0;
        $leaderboard->save();
        
        $leaderboard->updateRank();
    }

    public function getTopPerformers(string $period = 'all-time', int $limit = 10): array
    {
        if ($period === 'weekly') {
            return $this->getWeeklyLeaderboard($limit);
        } elseif ($period === 'monthly') {
            return $this->getMonthlyLeaderboard($limit);
        }
        
        return $this->getGlobalLeaderboard($limit);
    }

    public function getUserRankHistory(int $userId, int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        $history = DB::table('leaderboard_history')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at')
            ->get(['rank', 'created_at'])
            ->toArray();
        
        return $history;
    }

    public function calculatePoints(int $attemptId): int
    {
        $attempt = Attempt::with('quiz')->find($attemptId);
        
        if (!$attempt || $attempt->status !== 'completed') {
            return 0;
        }
        
        $basePoints = $attempt->score;
        $timeBonus = 0;
        $streakBonus = 0;
        
        if ($attempt->time_taken < ($attempt->quiz->time_limit * 60 * 0.5)) {
            $timeBonus = $basePoints * 0.1;
        }
        
        $consecutiveCorrect = Attempt::where('user_id', $attempt->user_id)
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->every(function ($a) {
                return $a->percentage_score >= 80;
            });
        
        if ($consecutiveCorrect) {
            $streakBonus = $basePoints * 0.2;
        }
        
        return (int) ($basePoints + $timeBonus + $streakBonus);
    }

    public function awardBonusPoints(int $userId, int $points, string $reason): bool
    {
        $leaderboard = Leaderboard::firstOrCreate(['user_id' => $userId]);
        
        $leaderboard->total_points += $points;
        $leaderboard->save();
        
        $leaderboard->updateRank();
        
        DB::table('bonus_points_log')->insert([
            'user_id' => $userId,
            'points' => $points,
            'reason' => $reason,
            'created_at' => now(),
        ]);
        
        return true;
    }

    public function getLeaderboardStats(): array
    {
        return [
            'total_users' => Leaderboard::count(),
            'total_points' => Leaderboard::sum('total_points'),
            'average_points' => Leaderboard::avg('total_points'),
            'total_quizzes_completed' => Leaderboard::sum('quizzes_completed'),
            'top_score' => Leaderboard::max('total_points'),
        ];
    }

    public function getCategoryLeaderboard(int $categoryId, int $limit = 50): array
    {
        return Cache::remember("leaderboard.category.{$categoryId}", 300, function () use ($categoryId, $limit) {
            $data = DB::table('attempts')
                ->join('users', 'attempts.user_id', '=', 'users.id')
                ->join('quizzes', 'attempts.quiz_id', '=', 'quizzes.id')
                ->where('quizzes.category_id', $categoryId)
                ->where('attempts.status', 'completed')
                ->select(
                    'users.id',
                    'users.name',
                    'users.avatar',
                    DB::raw('COUNT(DISTINCT attempts.id) as quizzes_completed'),
                    DB::raw('SUM(attempts.score) as category_points'),
                    DB::raw('AVG(attempts.percentage_score) as avg_score')
                )
                ->groupBy('users.id', 'users.name', 'users.avatar')
                ->orderBy('category_points', 'desc')
                ->limit($limit)
                ->get();

            $rank = 1;
            return $data->map(function ($item) use (&$rank) {
                return [
                    'rank' => $rank++,
                    'user' => [
                        'id' => $item->id,
                        'name' => $item->name,
                        'avatar' => $item->avatar,
                    ],
                    'category_points' => $item->category_points,
                    'quizzes_completed' => $item->quizzes_completed,
                    'average_score' => round($item->avg_score, 2),
                ];
            })->toArray();
        });
    }

    public function getFriendsLeaderboard(int $userId, int $limit = 20): array
    {
        $friendIds = DB::table('friends')
            ->where('user_id', $userId)
            ->orWhere('friend_id', $userId)
            ->pluck('friend_id');
        
        $friendIds[] = $userId;
        
        return Leaderboard::with('user')
            ->whereIn('user_id', $friendIds)
            ->orderBy('rank')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'rank' => $item->rank,
                    'user' => [
                        'id' => $item->user->id,
                        'name' => $item->user->name,
                        'avatar' => $item->user->avatar,
                    ],
                    'total_points' => $item->total_points,
                    'quizzes_completed' => $item->quizzes_completed,
                    'average_score' => round($item->average_score, 2),
                ];
            })
            ->toArray();
    }

    public function resetWeeklyLeaderboard(): void
    {
        Leaderboard::query()->update(['weekly_rank' => 0]);
        
        Cache::forget('leaderboard.weekly.*');
    }

    public function resetMonthlyLeaderboard(): void
    {
        Leaderboard::query()->update(['monthly_rank' => 0]);
        
        Cache::forget('leaderboard.monthly.*');
    }

    public function exportLeaderboard(string $type = 'global'): array
    {
        if ($type === 'weekly') {
            $data = $this->getWeeklyLeaderboard(1000);
        } elseif ($type === 'monthly') {
            $data = $this->getMonthlyLeaderboard(1000);
        } else {
            $data = $this->getGlobalLeaderboard(1000);
        }
        
        return $data;
    }

    public function getUserStreak(int $userId): array
    {
        $attempts = Attempt::where('user_id', $userId)
            ->where('status', 'completed')
            ->orderBy('completed_at')
            ->get(['completed_at']);
        
        if ($attempts->isEmpty()) {
            return [
                'current_streak' => 0,
                'longest_streak' => 0,
                'last_activity' => null,
            ];
        }
        
        $currentStreak = 0;
        $longestStreak = 0;
        $streak = 0;
        $lastDate = null;
        
        foreach ($attempts as $attempt) {
            $date = $attempt->completed_at->format('Y-m-d');
            
            if ($lastDate) {
                $diff = now()->parse($date)->diffInDays($lastDate);
                
                if ($diff == 1) {
                    $streak++;
                } elseif ($diff > 1) {
                    $longestStreak = max($longestStreak, $streak);
                    $streak = 1;
                }
            } else {
                $streak = 1;
            }
            
            $lastDate = $date;
        }
        
        $currentStreak = $streak;
        $longestStreak = max($longestStreak, $streak);
        
        if ($lastDate && now()->format('Y-m-d') != $lastDate) {
            $currentStreak = 0;
        }
        
        return [
            'current_streak' => $currentStreak,
            'longest_streak' => $longestStreak,
            'last_activity' => $lastDate,
        ];
    }

    public function updateUserStreak(int $userId): void
    {
        $streak = $this->getUserStreak($userId);
        
        DB::table('user_streaks')->updateOrInsert(
            ['user_id' => $userId],
            [
                'current_streak' => $streak['current_streak'],
                'longest_streak' => $streak['longest_streak'],
                'last_activity' => $streak['last_activity'],
                'updated_at' => now(),
            ]
        );
    }

    public function getTopImprovers(int $days = 7, int $limit = 10): array
    {
        $startDate = now()->subDays($days);
        
        $improvers = DB::table('attempts')
            ->join('users', 'attempts.user_id', '=', 'users.id')
            ->where('attempts.status', 'completed')
            ->where('attempts.completed_at', '>=', $startDate)
            ->select(
                'users.id',
                'users.name',
                'users.avatar',
                DB::raw('AVG(attempts.percentage_score) as avg_score'),
                DB::raw('COUNT(*) as attempts_count')
            )
            ->groupBy('users.id', 'users.name', 'users.avatar')
            ->orderBy('avg_score', 'desc')
            ->limit($limit)
            ->get();
        
        $rank = 1;
        return $improvers->map(function ($item) use (&$rank) {
            return [
                'rank' => $rank++,
                'user' => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'avatar' => $item->avatar,
                ],
                'average_score' => round($item->avg_score, 2),
                'attempts_count' => $item->attempts_count,
            ];
        })->toArray();
    }

    public function calculatePercentile(int $rank, int $totalUsers): float
    {
        if ($totalUsers === 0) {
            return 0;
        }
        
        return round((($totalUsers - $rank + 1) / $totalUsers) * 100, 2);
    }

    public function getAchievementProgress(int $userId): array
    {
        $user = User::with('achievements')->find($userId);
        
        if (!$user) {
            return [];
        }
        
        $allAchievements = DB::table('achievements')->count();
        $earnedAchievements = $user->achievements->count();
        
        return [
            'total_achievements' => $allAchievements,
            'earned_achievements' => $earnedAchievements,
            'progress_percentage' => $allAchievements > 0 ? round(($earnedAchievements / $allAchievements) * 100, 2) : 0,
            'recent_achievements' => $user->achievements()->latest('earned_at')->limit(5)->get()->toArray(),
        ];
    }

    public function getNextRankThreshold(int $userId): array
    {
        $leaderboard = Leaderboard::where('user_id', $userId)->first();
        
        if (!$leaderboard) {
            return [
                'current_rank' => null,
                'next_rank' => null,
                'points_needed' => null,
            ];
        }
        
        $nextRank = Leaderboard::where('rank', '<', $leaderboard->rank)
            ->orderBy('rank', 'desc')
            ->first();
        
        if (!$nextRank) {
            return [
                'current_rank' => $leaderboard->rank,
                'next_rank' => null,
                'points_needed' => null,
                'message' => 'You are at the top!',
            ];
        }
        
        $pointsNeeded = $nextRank->total_points - $leaderboard->total_points + 1;
        
        return [
            'current_rank' => $leaderboard->rank,
            'next_rank' => $nextRank->rank,
            'points_needed' => $pointsNeeded,
            'current_points' => $leaderboard->total_points,
            'target_points' => $nextRank->total_points,
        ];
    }

    public function getCountryLeaderboard(string $country, int $limit = 50): array
    {
        return Cache::remember("leaderboard.country.{$country}", 300, function () use ($country, $limit) {
            $data = Leaderboard::with('user')
                ->whereHas('user', function ($query) use ($country) {
                    $query->where('country', $country);
                })
                ->orderBy('rank')
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    return [
                        'rank' => $item->rank,
                        'user' => [
                            'id' => $item->user->id,
                            'name' => $item->user->name,
                            'avatar' => $item->user->avatar,
                        ],
                        'total_points' => $item->total_points,
                        'quizzes_completed' => $item->quizzes_completed,
                        'average_score' => round($item->average_score, 2),
                    ];
                })
                ->toArray();
            
            return $data;
        });
    }

    public function getTopQuizzes(int $limit = 10): array
    {
        return DB::table('quizzes')
            ->select(
                'quizzes.id',
                'quizzes.title',
                'quizzes.slug',
                DB::raw('COUNT(attempts.id) as attempt_count'),
                DB::raw('AVG(attempts.percentage_score) as avg_score')
            )
            ->leftJoin('attempts', 'quizzes.id', '=', 'attempts.quiz_id')
            ->where('attempts.status', 'completed')
            ->groupBy('quizzes.id', 'quizzes.title', 'quizzes.slug')
            ->orderBy('attempt_count', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getUserBestCategories(int $userId, int $limit = 5): array
    {
        return DB::table('attempts')
            ->join('quizzes', 'attempts.quiz_id', '=', 'quizzes.id')
            ->join('categories', 'quizzes.category_id', '=', 'categories.id')
            ->where('attempts.user_id', $userId)
            ->where('attempts.status', 'completed')
            ->select(
                'categories.id',
                'categories.name',
                DB::raw('AVG(attempts.percentage_score) as avg_score'),
                DB::raw('COUNT(*) as attempts_count')
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('avg_score', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getWeeklyWinners(int $weeks = 4): array
    {
        $winners = [];
        
        for ($i = 0; $i < $weeks; $i++) {
            $startOfWeek = now()->subWeeks($i)->startOfWeek();
            $endOfWeek = now()->subWeeks($i)->endOfWeek();
            
            $winner = DB::table('attempts')
                ->join('users', 'attempts.user_id', '=', 'users.id')
                ->where('attempts.status', 'completed')
                ->whereBetween('attempts.completed_at', [$startOfWeek, $endOfWeek])
                ->select(
                    'users.id',
                    'users.name',
                    DB::raw('SUM(attempts.score) as total_points')
                )
                ->groupBy('users.id', 'users.name')
                ->orderBy('total_points', 'desc')
                ->first();
            
            if ($winner) {
                $winners[] = [
                    'week' => $startOfWeek->format('M d, Y') . ' - ' . $endOfWeek->format('M d, Y'),
                    'user' => [
                        'id' => $winner->id,
                        'name' => $winner->name,
                    ],
                    'points' => $winner->total_points,
                ];
            }
        }
        
        return $winners;
    }

    public function getMonthlyChampions(int $months = 6): array
    {
        $champions = [];
        
        for ($i = 0; $i < $months; $i++) {
            $startOfMonth = now()->subMonths($i)->startOfMonth();
            $endOfMonth = now()->subMonths($i)->endOfMonth();
            
            $champion = DB::table('attempts')
                ->join('users', 'attempts.user_id', '=', 'users.id')
                ->where('attempts.status', 'completed')
                ->whereBetween('attempts.completed_at', [$startOfMonth, $endOfMonth])
                ->select(
                    'users.id',
                    'users.name',
                    DB::raw('SUM(attempts.score) as total_points')
                )
                ->groupBy('users.id', 'users.name')
                ->orderBy('total_points', 'desc')
                ->first();
            
            if ($champion) {
                $champions[] = [
                    'month' => $startOfMonth->format('F Y'),
                    'user' => [
                        'id' => $champion->id,
                        'name' => $champion->name,
                    ],
                    'points' => $champion->total_points,
                ];
            }
        }
        
        return $champions;
    }

    public function getHallOfFame(int $limit = 100): array
    {
        return Leaderboard::with('user')
            ->where('total_points', '>', 10000)
            ->orderBy('total_points', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($item, $index) {
                return [
                    'position' => $index + 1,
                    'user' => [
                        'id' => $item->user->id,
                        'name' => $item->user->name,
                        'avatar' => $item->user->avatar,
                    ],
                    'total_points' => $item->total_points,
                    'quizzes_completed' => $item->quizzes_completed,
                    'joined_date' => $item->user->created_at->format('Y-m-d'),
                ];
            })
            ->toArray();
    }

    public function recalculateAllScores(): void
    {
        $users = User::all();
        
        foreach ($users as $user) {
            $this->updateUserRank($user->id);
        }
        
        $this->updateAllRanks();
    }

    public function validateIntegrity(): array
    {
        $errors = [];
        
        $leaderboards = Leaderboard::all();
        
        foreach ($leaderboards as $leaderboard) {
            $calculatedPoints = Attempt::where('user_id', $leaderboard->user_id)
                ->where('status', 'completed')
                ->sum('score');
            
            if (abs($calculatedPoints - $leaderboard->total_points) > 0.01) {
                $errors[] = "User {$leaderboard->user_id} has incorrect total_points";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    public function archiveOldData(int $monthsOld = 6): int
    {
        $cutoffDate = now()->subMonths($monthsOld);
        
        $archived = DB::table('leaderboard_history')
            ->where('created_at', '<', $cutoffDate)
            ->get();
        
        if ($archived->isEmpty()) {
            return 0;
        }
        
        DB::beginTransaction();
        try {
            foreach ($archived as $item) {
                DB::table('leaderboard_history_archive')->insert((array) $item);
                DB::table('leaderboard_history')->where('id', $item->id)->delete();
            }
            DB::commit();
            return $archived->count();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getRealtimeUpdates(int $userId): array
    {
        $rank = $this->getUserRank($userId);
        $streak = $this->getUserStreak($userId);
        $progress = $this->getAchievementProgress($userId);
        
        return [
            'rank' => $rank,
            'streak' => $streak,
            'achievement_progress' => $progress,
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    public function getHeatMapData(): array
    {
        $data = DB::table('attempts')
            ->select(
                DB::raw('DATE(completed_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('status', 'completed')
            ->where('completed_at', '>=', now()->subYear())
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
        
        return $data;
    }

    public function sendWeeklyLeaderboardEmails(): void
    {
        $topUsers = $this->getWeeklyLeaderboard(10);
        
        foreach ($topUsers as $user) {
            // Send email logic here
            // Mail::to($user['user']['email'])->send(new WeeklyLeaderboardMail($user));
        }
    }

    public function getBadgeProgression(int $userId): array
    {
        $user = User::with('achievements')->find($userId);
        
        if (!$user) {
            return [];
        }
        
        $badges = [
            'bronze' => ['threshold' => 1000, 'earned' => false],
            'silver' => ['threshold' => 5000, 'earned' => false],
            'gold' => ['threshold' => 10000, 'earned' => false],
            'platinum' => ['threshold' => 50000, 'earned' => false],
            'diamond' => ['threshold' => 100000, 'earned' => false],
        ];
        
        $totalPoints = $user->leaderboard->total_points ?? 0;
        
        foreach ($badges as $name => &$badge) {
            $badge['earned'] = $totalPoints >= $badge['threshold'];
            $badge['progress'] = min(100, round(($totalPoints / $badge['threshold']) * 100, 2));
            $badge['points_needed'] = max(0, $badge['threshold'] - $totalPoints);
        }
        
        return $badges;
    }

    public function claimWeeklyReward(int $userId): bool
    {
        $lastClaim = Cache::get("weekly_reward.{$userId}");
        
        if ($lastClaim && now()->diffInDays($lastClaim) < 7) {
            return false;
        }
        
        $rank = $this->getUserWeeklyRank($userId);
        
        if (!$rank || $rank['rank'] > 10) {
            return false;
        }
        
        $rewardPoints = [1000, 500, 250, 100, 50, 25, 10, 5, 5, 5];
        $points = $rewardPoints[$rank['rank'] - 1] ?? 5;
        
        $this->awardBonusPoints($userId, $points, 'Weekly top 10 reward');
        
        Cache::put("weekly_reward.{$userId}", now(), now()->addDays(7));
        
        return true;
    }

    public function searchLeaderboard(string $query): array
    {
        return Leaderboard::with('user')
            ->whereHas('user', function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('email', 'LIKE', "%{$query}%");
            })
            ->orderBy('rank')
            ->limit(20)
            ->get()
            ->map(function ($item) {
                return [
                    'rank' => $item->rank,
                    'user' => [
                        'id' => $item->user->id,
                        'name' => $item->user->name,
                        'avatar' => $item->user->avatar,
                    ],
                    'total_points' => $item->total_points,
                ];
            })
            ->toArray();
    }
}