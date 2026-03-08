<?php

namespace App\Services\Interfaces;

interface LeaderboardServiceInterface
{
    /**
     * Get global leaderboard.
     *
     * @param int $limit
     * @return array
     */
    public function getGlobalLeaderboard(int $limit = 50): array;

    /**
     * Get weekly leaderboard.
     *
     * @param int $limit
     * @return array
     */
    public function getWeeklyLeaderboard(int $limit = 50): array;

    /**
     * Get monthly leaderboard.
     *
     * @param int $limit
     * @return array
     */
    public function getMonthlyLeaderboard(int $limit = 50): array;

    /**
     * Get user rank.
     *
     * @param int $userId
     * @param string $type (global|weekly|monthly)
     * @return array|null
     */
    public function getUserRank(int $userId, string $type = 'global'): ?array;

    /**
     * Update all ranks.
     *
     * @return void
     */
    public function updateAllRanks(): void;

    /**
     * Update user rank.
     *
     * @param int $userId
     * @return void
     */
    public function updateUserRank(int $userId): void;

    /**
     * Get top performers.
     *
     * @param string $period (all-time|weekly|monthly)
     * @param int $limit
     * @return array
     */
    public function getTopPerformers(string $period = 'all-time', int $limit = 10): array;

    /**
     * Get user position history.
     *
     * @param int $userId
     * @param int $days
     * @return array
     */
    public function getUserRankHistory(int $userId, int $days = 30): array;

    /**
     * Calculate points for attempt.
     *
     * @param int $attemptId
     * @return int
     */
    public function calculatePoints(int $attemptId): int;

    /**
     * Award bonus points.
     *
     * @param int $userId
     * @param int $points
     * @param string $reason
     * @return bool
     */
    public function awardBonusPoints(int $userId, int $points, string $reason): bool;

    /**
     * Get leaderboard statistics.
     *
     * @return array
     */
    public function getLeaderboardStats(): array;

    /**
     * Get category-wise leaderboard.
     *
     * @param int $categoryId
     * @param int $limit
     * @return array
     */
    public function getCategoryLeaderboard(int $categoryId, int $limit = 50): array;

    /**
     * Get friends leaderboard.
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getFriendsLeaderboard(int $userId, int $limit = 20): array;

    /**
     * Reset weekly leaderboard.
     *
     * @return void
     */
    public function resetWeeklyLeaderboard(): void;

    /**
     * Reset monthly leaderboard.
     *
     * @return void
     */
    public function resetMonthlyLeaderboard(): void;

    /**
     * Export leaderboard data.
     *
     * @param string $type
     * @return array
     */
    public function exportLeaderboard(string $type = 'global'): array;

    /**
     * Get user streak information.
     *
     * @param int $userId
     * @return array
     */
    public function getUserStreak(int $userId): array;

    /**
     * Update user streak.
     *
     * @param int $userId
     * @return void
     */
    public function updateUserStreak(int $userId): void;

    /**
     * Get top improvers.
     *
     * @param int $days
     * @param int $limit
     * @return array
     */
    public function getTopImprovers(int $days = 7, int $limit = 10): array;

    /**
     * Calculate rank percentile.
     *
     * @param int $rank
     * @param int $totalUsers
     * @return float
     */
    public function calculatePercentile(int $rank, int $totalUsers): float;

    /**
     * Get achievement progress for user.
     *
     * @param int $userId
     * @return array
     */
    public function getAchievementProgress(int $userId): array;

    /**
     * Get next rank threshold.
     *
     * @param int $userId
     * @return array
     */
    public function getNextRankThreshold(int $userId): array;

    /**
     * Get leaderboard by country.
     *
     * @param string $country
     * @param int $limit
     * @return array
     */
    public function getCountryLeaderboard(string $country, int $limit = 50): array;

    /**
     * Get top quizzes by popularity.
     *
     * @param int $limit
     * @return array
     */
    public function getTopQuizzes(int $limit = 10): array;

    /**
     * Get user's best categories.
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getUserBestCategories(int $userId, int $limit = 5): array;

    /**
     * Get weekly winners.
     *
     * @param int $weeks
     * @return array
     */
    public function getWeeklyWinners(int $weeks = 4): array;

    /**
     * Get monthly champions.
     *
     * @param int $months
     * @return array
     */
    public function getMonthlyChampions(int $months = 6): array;

    /**
     * Get hall of fame.
     *
     * @param int $limit
     * @return array
     */
    public function getHallOfFame(int $limit = 100): array;

    /**
     * Recalculate all scores.
     *
     * @return void
     */
    public function recalculateAllScores(): void;

    /**
     * Validate leaderboard integrity.
     *
     * @return array
     */
    public function validateIntegrity(): array;

    /**
     * Archive old leaderboard data.
     *
     * @param int $monthsOld
     * @return int
     */
    public function archiveOldData(int $monthsOld = 6): int;

    /**
     * Get real-time leaderboard updates.
     *
     * @param int $userId
     * @return array
     */
    public function getRealtimeUpdates(int $userId): array;

    /**
     * Get leaderboard heat map data.
     *
     * @return array
     */
    public function getHeatMapData(): array;

    /**
     * Send weekly leaderboard emails.
     *
     * @return void
     */
    public function sendWeeklyLeaderboardEmails(): void;

    /**
     * Get badge progression.
     *
     * @param int $userId
     * @return array
     */
    public function getBadgeProgression(int $userId): array;

    /**
     * Claim weekly reward.
     *
     * @param int $userId
     * @return bool
     */
    public function claimWeeklyReward(int $userId): bool;

    /**
     * Get leaderboard search results.
     *
     * @param string $query
     * @return array
     */
    public function searchLeaderboard(string $query): array;
}