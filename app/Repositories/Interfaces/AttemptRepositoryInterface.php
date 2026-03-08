<?php

namespace App\Repositories\Interfaces;

use App\Models\Attempt;
use Illuminate\Pagination\LengthAwarePaginator;

interface AttemptRepositoryInterface
{
    /**
     * Find attempt by ID.
     *
     * @param int $id
     * @return Attempt|null
     */
    public function findById(int $id): ?Attempt;

    /**
     * Find attempt with all details (answers, questions, etc).
     *
     * @param int $id
     * @return Attempt|null
     */
    public function findWithDetails(int $id): ?Attempt;

    /**
     * Find in-progress attempt for user on a quiz.
     *
     * @param int $userId
     * @param int $quizId
     * @return Attempt|null
     */
    public function findInProgress(int $userId, int $quizId): ?Attempt;

    /**
     * Create a new attempt.
     *
     * @param array $data
     * @return Attempt
     */
    public function create(array $data): Attempt;

    /**
     * Update an existing attempt.
     *
     * @param int $id
     * @param array $data
     * @return Attempt
     */
    public function update(int $id, array $data): Attempt;

    /**
     * Delete an attempt.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Get all attempts by user with pagination.
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByUser(int $userId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get all attempts by quiz with pagination.
     *
     * @param int $quizId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByQuiz(int $quizId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get completed attempts by user.
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getCompletedByUser(int $userId, int $limit = 10): array;

    /**
     * Get attempts by date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getByDateRange(string $startDate, string $endDate): array;

    /**
     * Get attempts statistics for a quiz.
     *
     * @param int $quizId
     * @return array
     */
    public function getQuizStats(int $quizId): array;

    /**
     * Get user's best attempts.
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getUserBestAttempts(int $userId, int $limit = 5): array;

    /**
     * Get user's recent attempts.
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getUserRecentAttempts(int $userId, int $limit = 5): array;

    /**
     * Get attempts count by status.
     *
     * @param int $userId
     * @return array
     */
    public function getAttemptsCountByStatus(int $userId): array;

    /**
     * Get average score per quiz for user.
     *
     * @param int $userId
     * @return array
     */
    public function getAverageScorePerQuiz(int $userId): array;

    /**
     * Get top scores for a quiz.
     *
     * @param int $quizId
     * @param int $limit
     * @return array
     */
    public function getTopScores(int $quizId, int $limit = 10): array;

    /**
     * Count completed attempts by user and quiz.
     *
     * @param int $userId
     * @param int $quizId
     * @return int
     */
    public function countCompletedByUserAndQuiz(int $userId, int $quizId): int;

    /**
     * Get attempts trend over time.
     *
     * @param int $days
     * @return array
     */
    public function getAttemptsTrend(int $days = 30): array;

    /**
     * Get attempts by difficulty level.
     *
     * @param string $difficulty
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByDifficulty(string $difficulty, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get attempts by category.
     *
     * @param int $categoryId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get attempts needing review.
     *
     * @param int $threshold
     * @return array
     */
    public function getAttemptsNeedingReview(int $threshold = 50): array;

    /**
     * Get user's improvement over time.
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getUserImprovementTrend(int $userId, int $limit = 10): array;

    /**
     * Get average completion time per quiz.
     *
     * @param int $quizId
     * @return float
     */
    public function getAverageCompletionTime(int $quizId): float;

    /**
     * Get pass rate for a quiz.
     *
     * @param int $quizId
     * @return float
     */
    public function getPassRate(int $quizId): float;

    /**
     * Get completion rate (completed vs started).
     *
     * @param int $quizId
     * @return float
     */
    public function getCompletionRate(int $quizId): float;

    /**
     * Get daily attempt counts.
     *
     * @param int $days
     * @return array
     */
    public function getDailyAttemptCounts(int $days = 7): array;

    /**
     * Get hourly attempt distribution.
     *
     * @return array
     */
    public function getHourlyDistribution(): array;

    /**
     * Get weekday distribution.
     *
     * @return array
     */
    public function getWeekdayDistribution(): array;

    /**
     * Get monthly attempt statistics.
     *
     * @param int $year
     * @return array
     */
    public function getMonthlyStats(int $year = null): array;

    /**
     * Get recent activity feed.
     *
     * @param int $limit
     * @return array
     */
    public function getRecentActivity(int $limit = 20): array;

    /**
     * Get user ranking among peers.
     *
     * @param int $userId
     * @param int $quizId
     * @return array
     */
    public function getUserRanking(int $userId, int $quizId): array;

    /**
     * Get peer comparison.
     *
     * @param int $userId
     * @param int $quizId
     * @return array
     */
    public function getPeerComparison(int $userId, int $quizId): array;

    /**
     * Get attempt metadata.
     *
     * @param int $attemptId
     * @return array
     */
    public function getAttemptMetadata(int $attemptId): array;

    /**
     * Get attempts by IP address.
     *
     * @param string $ip
     * @param int $limit
     * @return array
     */
    public function getByIp(string $ip, int $limit = 50): array;

    /**
     * Get suspicious attempts (multiple attempts from same IP, etc).
     *
     * @return array
     */
    public function getSuspiciousAttempts(): array;

    /**
     * Export attempts for reporting.
     *
     * @param array $filters
     * @return array
     */
    public function exportForReporting(array $filters = []): array;

    /**
     * Get attempt count by hour of day.
     *
     * @return array
     */
    public function getCountByHour(): array;

    /**
     * Get attempt count by day of week.
     *
     * @return array
     */
    public function getCountByDayOfWeek(): array;

    /**
     * Get average score by hour.
     *
     * @return array
     */
    public function getAverageScoreByHour(): array;

    /**
     * Get average score by day of week.
     *
     * @return array
     */
    public function getAverageScoreByDayOfWeek(): array;

    /**
     * Get success rate trend.
     *
     * @param int $days
     * @return array
     */
    public function getSuccessRateTrend(int $days = 30): array;

    /**
     * Get first attempt success rate.
     *
     * @param int $quizId
     * @return float
     */
    public function getFirstAttemptSuccessRate(int $quizId): float;

    /**
     * Get retry improvement rate.
     *
     * @param int $quizId
     * @return float
     */
    public function getRetryImprovementRate(int $quizId): float;

    /**
     * Get attempts by score range.
     *
     * @param int $quizId
     * @param int $minScore
     * @param int $maxScore
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByScoreRange(int $quizId, int $minScore, int $maxScore, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get attempts by time range.
     *
     * @param int $quizId
     * @param int $minTime
     * @param int $maxTime
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByTimeRange(int $quizId, int $minTime, int $maxTime, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get user's attempt history with filters.
     *
     * @param int $userId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUserHistory(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get quiz attempt history with filters.
     *
     * @param int $quizId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getQuizHistory(int $quizId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get attempts count by date.
     *
     * @param string $date
     * @return int
     */
    public function getCountByDate(string $date): int;

    /**
     * Get attempts count by month.
     *
     * @param int $year
     * @param int $month
     * @return int
     */
    public function getCountByMonth(int $year, int $month): int;

    /**
     * Get average score by date.
     *
     * @param string $date
     * @return float
     */
    public function getAverageScoreByDate(string $date): float;

    /**
     * Get average score by month.
     *
     * @param int $year
     * @param int $month
     * @return float
     */
    public function getAverageScoreByMonth(int $year, int $month): float;

    /**
     * Get total attempts count.
     *
     * @param array $filters
     * @return int
     */
    public function getTotalAttemptsCount(array $filters = []): int;

    /**
     * Get total completed attempts count.
     *
     * @param array $filters
     * @return int
     */
    public function getTotalCompletedCount(array $filters = []): int;

    /**
     * Get total in-progress attempts count.
     *
     * @param array $filters
     * @return int
     */
    public function getTotalInProgressCount(array $filters = []): int;

    /**
     * Get total timed-out attempts count.
     *
     * @param array $filters
     * @return int
     */
    public function getTotalTimedOutCount(array $filters = []): int;

    /**
     * Get overall average score.
     *
     * @param array $filters
     * @return float
     */
    public function getOverallAverageScore(array $filters = []): float;

    /**
     * Get overall average time taken.
     *
     * @param array $filters
     * @return float
     */
    public function getOverallAverageTime(array $filters = []): float;

    /**
     * Get users with most attempts.
     *
     * @param int $limit
     * @return array
     */
    public function getTopActiveUsers(int $limit = 10): array;

    /**
     * Get quizzes with most attempts.
     *
     * @param int $limit
     * @return array
     */
    public function getTopAttemptedQuizzes(int $limit = 10): array;

    /**
     * Get users with highest average score.
     *
     * @param int $limit
     * @param int $minAttempts
     * @return array
     */
    public function getTopPerformingUsers(int $limit = 10, int $minAttempts = 5): array;

    /**
     * Get quizzes with highest average score.
     *
     * @param int $limit
     * @param int $minAttempts
     * @return array
     */
    public function getTopPerformingQuizzes(int $limit = 10, int $minAttempts = 5): array;

    /**
     * Get attempt statistics by user segment.
     *
     * @param string $segment
     * @return array
     */
    public function getStatsByUserSegment(string $segment): array;

    /**
     * Get attempt statistics by quiz category.
     *
     * @return array
     */
    public function getStatsByCategory(): array;

    /**
     * Get attempt statistics by difficulty.
     *
     * @return array
     */
    public function getStatsByDifficulty(): array;

    /**
     * Get completion funnel data.
     *
     * @param int $quizId
     * @return array
     */
    public function getCompletionFunnel(int $quizId): array;

    /**
     * Get drop-off points (questions where users quit).
     *
     * @param int $quizId
     * @return array
     */
    public function getDropOffPoints(int $quizId): array;

    /**
     * Get time distribution for quiz completion.
     *
     * @param int $quizId
     * @return array
     */
    public function getTimeDistribution(int $quizId): array;

    /**
     * Get score distribution for quiz.
     *
     * @param int $quizId
     * @param int $buckets
     * @return array
     */
    public function getScoreDistribution(int $quizId, int $buckets = 10): array;

    /**
     * Get attempts comparison between two periods.
     *
     * @param string $startDate1
     * @param string $endDate1
     * @param string $startDate2
     * @param string $endDate2
     * @return array
     */
    public function getPeriodComparison(string $startDate1, string $endDate1, string $startDate2, string $endDate2): array;

    /**
     * Get year-over-year growth.
     *
     * @param int $years
     * @return array
     */
    public function getYearOverYearGrowth(int $years = 2): array;

    /**
     * Get month-over-month growth.
     *
     * @param int $months
     * @return array
     */
    public function getMonthOverMonthGrowth(int $months = 6): array;

    /**
     * Get predicted attempts for next period.
     *
     * @param int $days
     * @return array
     */
    public function getPredictedAttempts(int $days = 30): array;

    /**
     * Get seasonal patterns.
     *
     * @return array
     */
    public function getSeasonalPatterns(): array;

    /**
     * Get holiday impact on attempts.
     *
     * @param array $holidays
     * @return array
     */
    public function getHolidayImpact(array $holidays): array;

    /**
     * Get weather impact on attempts (if weather data available).
     *
     * @param array $weatherData
     * @return array
     */
    public function getWeatherImpact(array $weatherData): array;

    /**
     * Get attempt correlation with other factors.
     *
     * @param string $factor
     * @return array
     */
    public function getCorrelation(string $factor): array;

    /**
     * Export attempts in bulk for data science.
     *
     * @param array $options
     * @return array
     */
    public function exportForDataScience(array $options = []): array;

    /**
     * Get attempts sample for machine learning.
     *
     * @param int $size
     * @param array $conditions
     * @return array
     */
    public function getMLSample(int $size = 1000, array $conditions = []): array;

    /**
     * Get feature importance for prediction.
     *
     * @return array
     */
    public function getFeatureImportance(): array;

    /**
     * Get anomaly detection results.
     *
     * @param string $method
     * @return array
     */
    public function getAnomalies(string $method = 'statistical'): array;

    /**
     * Get attempt patterns by user behavior.
     *
     * @return array
     */
    public function getUserBehaviorPatterns(): array;

    /**
     * Get learning curve data.
     *
     * @param int $userId
     * @param int $quizId
     * @return array
     */
    public function getLearningCurve(int $userId, int $quizId): array;

    /**
     * Get forgetting curve data.
     *
     * @param int $userId
     * @param int $quizId
     * @param int $days
     * @return array
     */
    public function getForgettingCurve(int $userId, int $quizId, int $days = 30): array;

    /**
     * Get spaced repetition recommendations.
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getSpacedRepetitionRecommendations(int $userId, int $limit = 10): array;

    /**
     * Get mastery level for user on quiz.
     *
     * @param int $userId
     * @param int $quizId
     * @return float
     */
    public function getMasteryLevel(int $userId, int $quizId): float;

    /**
     * Get knowledge retention rate.
     *
     * @param int $userId
     * @param int $quizId
     * @return float
     */
    public function getRetentionRate(int $userId, int $quizId): float;

    /**
     * Get confusion matrix for answers.
     *
     * @param int $quizId
     * @return array
     */
    public function getConfusionMatrix(int $quizId): array;

    /**
     * Get question difficulty progression.
     *
     * @param int $quizId
     * @return array
     */
    public function getQuestionDifficultyProgression(int $quizId): array;

    /**
     * Get optimal question order based on performance.
     *
     * @param int $quizId
     * @return array
     */
    public function getOptimalQuestionOrder(int $quizId): array;

    /**
     * Get personalized difficulty adjustment.
     *
     * @param int $userId
     * @param int $quizId
     * @return array
     */
    public function getPersonalizedDifficulty(int $userId, int $quizId): array;

    /**
     * Get adaptive testing parameters.
     *
     * @param int $userId
     * @param int $quizId
     * @return array
     */
    public function getAdaptiveTestingParams(int $userId, int $quizId): array;

    /**
     * Get item response theory parameters.
     *
     * @param int $questionId
     * @return array
     */
    public function getIRTParameters(int $questionId): array;

    /**
     * Get question discrimination index.
     *
     * @param int $questionId
     * @return float
     */
    public function getDiscriminationIndex(int $questionId): float;

    /**
     * Get question difficulty index.
     *
     * @param int $questionId
     * @return float
     */
    public function getDifficultyIndex(int $questionId): float;

    /**
     * Get question guessing parameter.
     *
     * @param int $questionId
     * @return float
     */
    public function getGuessingParameter(int $questionId): float;

    /**
     * Get test information function.
     *
     * @param int $quizId
     * @return array
     */
    public function getTestInformationFunction(int $quizId): array;

    /**
     * Get standard error of measurement.
     *
     * @param int $quizId
     * @return float
     */
    public function getStandardErrorOfMeasurement(int $quizId): float;

    /**
     * Get reliability coefficient (Cronbach's alpha).
     *
     * @param int $quizId
     * @return float
     */
    public function getReliabilityCoefficient(int $quizId): float;

    /**
     * Get item-total correlation.
     *
     * @param int $questionId
     * @return float
     */
    public function getItemTotalCorrelation(int $questionId): float;

    /**
     * Get KR-20 reliability.
     *
     * @param int $quizId
     * @return float
     */
    public function getKR20Reliability(int $quizId): float;

    /**
     * Get test-retest reliability.
     *
     * @param int $quizId
     * @param int $days
     * @return float
     */
    public function getTestRetestReliability(int $quizId, int $days = 30): float;

    /**
     * Get split-half reliability.
     *
     * @param int $quizId
     * @return float
     */
    public function getSplitHalfReliability(int $quizId): float;

    /**
     * Get parallel forms reliability.
     *
     * @param int $quizId1
     * @param int $quizId2
     * @return float
     */
    public function getParallelFormsReliability(int $quizId1, int $quizId2): float;

    /**
     * Get inter-rater reliability (for essay questions).
     *
     * @param int $quizId
     * @return float
     */
    public function getInterRaterReliability(int $quizId): float;

    /**
     * Get validity coefficient.
     *
     * @param int $quizId
     * @param string $criterion
     * @return float
     */
    public function getValidityCoefficient(int $quizId, string $criterion): float;

    /**
     * Get content validity index.
     *
     * @param int $quizId
     * @return float
     */
    public function getContentValidityIndex(int $quizId): float;

    /**
     * Get construct validity.
     *
     * @param int $quizId
     * @return array
     */
    public function getConstructValidity(int $quizId): array;

    /**
     * Get criterion-related validity.
     *
     * @param int $quizId
     * @param string $criterion
     * @return float
     */
    public function getCriterionValidity(int $quizId, string $criterion): float;

    /**
     * Get concurrent validity.
     *
     * @param int $quizId
     * @param int $otherQuizId
     * @return float
     */
    public function getConcurrentValidity(int $quizId, int $otherQuizId): float;

    /**
     * Get predictive validity.
     *
     * @param int $quizId
     * @param string $outcome
     * @return float
     */
    public function getPredictiveValidity(int $quizId, string $outcome): float;

    /**
     * Get face validity.
     *
     * @param int $quizId
     * @return float
     */
    public function getFaceValidity(int $quizId): float;

    /**
     * Get factorial validity.
     *
     * @param int $quizId
     * @return array
     */
    public function getFactorialValidity(int $quizId): array;

    /**
     * Get differential item functioning.
     *
     * @param int $questionId
     * @param string $group
     * @return array
     */
    public function getDifferentialItemFunctioning(int $questionId, string $group): array;

    /**
     * Get item bias.
     *
     * @param int $questionId
     * @param string $group
     * @return float
     */
    public function getItemBias(int $questionId, string $group): float;

    /**
     * Get test fairness.
     *
     * @param int $quizId
     * @param string $group
     * @return array
     */
    public function getTestFairness(int $quizId, string $group): array;

    /**
     * Get measurement invariance.
     *
     * @param int $quizId
     * @param string $group
     * @return array
     */
    public function getMeasurementInvariance(int $quizId, string $group): array;

    /**
     * Get differential test functioning.
     *
     * @param int $quizId
     * @param string $group
     * @return array
     */
    public function getDifferentialTestFunctioning(int $quizId, string $group): array;

    /**
     * Get impact assessment.
     *
     * @param int $quizId
     * @param string $group
     * @return array
     */
    public function getImpactAssessment(int $quizId, string $group): array;

    /**
     * Get adverse impact ratio.
     *
     * @param int $quizId
     * @param string $group
     * @return float
     */
    public function getAdverseImpactRatio(int $quizId, string $group): float;

    /**
     * Get four-fifths rule compliance.
     *
     * @param int $quizId
     * @param string $group
     * @return bool
     */
    public function getFourFifthsRuleCompliance(int $quizId, string $group): bool;

    /**
     * Get test utility analysis.
     *
     * @param int $quizId
     * @return array
     */
    public function getTestUtilityAnalysis(int $quizId): array;

    /**
     * Get return on investment.
     *
     * @param int $quizId
     * @return float
     */
    public function getReturnOnInvestment(int $quizId): float;

    /**
     * Get cost-benefit analysis.
     *
     * @param int $quizId
     * @return array
     */
    public function getCostBenefitAnalysis(int $quizId): array;

    /**
     * Get efficiency index.
     *
     * @param int $quizId
     * @return float
     */
    public function getEfficiencyIndex(int $quizId): float;

    /**
     * Get effectiveness index.
     *
     * @param int $quizId
     * @return float
     */
    public function getEffectivenessIndex(int $quizId): float;

    /**
     * Get productivity index.
     *
     * @param int $quizId
     * @return float
     */
    public function getProductivityIndex(int $quizId): float;

    /**
     * Get quality index.
     *
     * @param int $quizId
     * @return float
     */
    public function getQualityIndex(int $quizId): float;

    /**
     * Get satisfaction index.
     *
     * @param int $quizId
     * @return float
     */
    public function getSatisfactionIndex(int $quizId): float;

    /**
     * Get engagement index.
     *
     * @param int $quizId
     * @return float
     */
    public function getEngagementIndex(int $quizId): float;

    /**
     * Get retention index.
     *
     * @param int $quizId
     * @return float
     */
    public function getRetentionIndex(int $quizId): float;

    /**
     * Get completion index.
     *
     * @param int $quizId
     * @return float
     */
    public function getCompletionIndex(int $quizId): float;

    /**
     * Get success index.
     *
     * @param int $quizId
     * @return float
     */
    public function getSuccessIndex(int $quizId): float;

    /**
     * Get performance index.
     *
     * @param int $quizId
     * @return float
     */
    public function getPerformanceIndex(int $quizId): float;

    /**
     * Get mastery index.
     *
     * @param int $quizId
     * @return float
     */
    public function getMasteryIndex(int $quizId): float;

    /**
     * Get learning index.
     *
     * @param int $quizId
     * @return float
     */
    public function getLearningIndex(int $quizId): float;

    /**
     * Get growth index.
     *
     * @param int $quizId
     * @return float
     */
    public function getGrowthIndex(int $quizId): float;

    /**
     * Get improvement index.
     *
     * @param int $quizId
     * @return float
     */
    public function getImprovementIndex(int $quizId): float;

    /**
     * Get progress index.
     *
     * @param int $quizId
     * @return float
     */
    public function getProgressIndex(int $quizId): float;

    /**
     * Get achievement index.
     *
     * @param int $quizId
     * @return float
     */
    public function getAchievementIndex(int $quizId): float;

    /**
     * Get recognition index.
     *
     * @param int $quizId
     * @return float
     */
    public function getRecognitionIndex(int $quizId): float;

    /**
     * Get reward index.
     *
     * @param int $quizId
     * @return float
     */
    public function getRewardIndex(int $quizId): float;

    /**
     * Get motivation index.
     *
     * @param int $quizId
     * @return float
     */
    public function getMotivationIndex(int $quizId): float;

    /**
     * Get confidence index.
     *
     * @param int $quizId
     * @return float
     */
    public function getConfidenceIndex(int $quizId): float;

    /**
     * Get self-efficacy index.
     *
     * @param int $quizId
     * @return float
     */
    public function getSelfEfficacyIndex(int $quizId): float;

    /**
     * Get anxiety index.
     *
     * @param int $quizId
     * @return float
     */
    public function getAnxietyIndex(int $quizId): float;

    /**
     * Get stress index.
     *
     * @param int $quizId
     * @return float
     */
    public function getStressIndex(int $quizId): float;

    /**
     * Get fatigue index.
     *
     * @param int $quizId
     * @return float
     */
    public function getFatigueIndex(int $quizId): float;

    /**
     * Get boredom index.
     *
     * @param int $quizId
     * @return float
     */
    public function getBoredomIndex(int $quizId): float;

    /**
     * Get frustration index.
     *
     * @param int $quizId
     * @return float
     */
    public function getFrustrationIndex(int $quizId): float;

    /**
     * Get confusion index.
     *
     * @param int $quizId
     * @return float
     */
    public function getConfusionIndex(int $quizId): float;

    /**
     * Get interest index.
     *
     * @param int $quizId
     * @return float
     */
    public function getInterestIndex(int $quizId): float;

    /**
     * Get relevance index.
     *
     * @param int $quizId
     * @return float
     */
    public function getRelevanceIndex(int $quizId): float;

    /**
     * Get usefulness index.
     *
     * @param int $quizId
     * @return float
     */
    public function getUsefulnessIndex(int $quizId): float;

    /**
     * Get applicability index.
     *
     * @param int $quizId
     * @return float
     */
    public function getApplicabilityIndex(int $quizId): float;

    /**
     * Get transfer index.
     *
     * @param int $quizId
     * @return float
     */
    public function getTransferIndex(int $quizId): float;

    /**
     * Get retention curve.
     *
     * @param int $quizId
     * @param int $days
     * @return array
     */
    public function getRetentionCurve(int $quizId, int $days = 30): array;

    /**
     * Get power law of learning.
     *
     * @param int $quizId
     * @return array
     */
    public function getPowerLawOfLearning(int $quizId): array;

    /**
     * Get Ebbinghaus forgetting curve.
     *
     * @param int $quizId
     * @return array
     */
    public function getEbbinghausForgettingCurve(int $quizId): array;

    /**
     * Get spaced repetition effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getSpacedRepetitionEffect(int $quizId): array;

    /**
     * Get testing effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getTestingEffect(int $quizId): array;

    /**
     * Get retrieval practice effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getRetrievalPracticeEffect(int $quizId): array;

    /**
     * Get generation effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getGenerationEffect(int $quizId): array;

    /**
     * Get elaboration effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getElaborationEffect(int $quizId): array;

    /**
     * Get organization effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getOrganizationEffect(int $quizId): array;

    /**
     * Get visualization effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getVisualizationEffect(int $quizId): array;

    /**
     * Get dual coding effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getDualCodingEffect(int $quizId): array;

    /**
     * Get multimedia effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getMultimediaEffect(int $quizId): array;

    /**
     * Get modality effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getModalityEffect(int $quizId): array;

    /**
     * Get redundancy effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getRedundancyEffect(int $quizId): array;

    /**
     * Get coherence effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getCoherenceEffect(int $quizId): array;

    /**
     * Get personalization effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getPersonalizationEffect(int $quizId): array;

    /**
     * Get embodiment effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getEmbodimentEffect(int $quizId): array;

    /**
     * Get emotional design effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getEmotionalDesignEffect(int $quizId): array;

    /**
     * Get seductive details effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getSeductiveDetailsEffect(int $quizId): array;

    /**
     * Get signaling effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getSignalingEffect(int $quizId): array;

    /**
     * Get cueing effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getCueingEffect(int $quizId): array;

    /**
     * Get feedback effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getFeedbackEffect(int $quizId): array;

    /**
     * Get scaffolding effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getScaffoldingEffect(int $quizId): array;

    /**
     * Get fading effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getFadingEffect(int $quizId): array;

    /**
     * Get worked example effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getWorkedExampleEffect(int $quizId): array;

    /**
     * Get completion effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getCompletionEffect(int $quizId): array;

    /**
     * Get imagination effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getImaginationEffect(int $quizId): array;

    /**
     * Get self-explanation effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getSelfExplanationEffect(int $quizId): array;

    /**
     * Get reflection effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getReflectionEffect(int $quizId): array;

    /**
     * Get metacognition effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getMetacognitionEffect(int $quizId): array;

    /**
     * Get self-regulation effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getSelfRegulationEffect(int $quizId): array;

    /**
     * Get motivation effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getMotivationEffect(int $quizId): array;

    /**
     * Get engagement effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getEngagementEffect(int $quizId): array;

    /**
     * Get persistence effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getPersistenceEffect(int $quizId): array;

    /**
     * Get grit effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getGritEffect(int $quizId): array;

    /**
     * Get mindset effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getMindsetEffect(int $quizId): array;

    /**
     * Get attribution effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getAttributionEffect(int $quizId): array;

    /**
     * Get self-efficacy effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getSelfEfficacyEffect(int $quizId): array;

    /**
     * Get confidence effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getConfidenceEffect(int $quizId): array;

    /**
     * Get anxiety effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getAnxietyEffect(int $quizId): array;

    /**
     * Get stress effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getStressEffect(int $quizId): array;

    /**
     * Get fatigue effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getFatigueEffect(int $quizId): array;

    /**
     * Get boredom effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getBoredomEffect(int $quizId): array;

    /**
     * Get frustration effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getFrustrationEffect(int $quizId): array;

    /**
     * Get confusion effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getConfusionEffect(int $quizId): array;

    /**
     * Get curiosity effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getCuriosityEffect(int $quizId): array;

    /**
     * Get interest effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getInterestEffect(int $quizId): array;

    /**
     * Get relevance effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getRelevanceEffect(int $quizId): array;

    /**
     * Get usefulness effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getUsefulnessEffect(int $quizId): array;

    /**
     * Get applicability effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getApplicabilityEffect(int $quizId): array;

    /**
     * Get transfer effect.
     *
     * @param int $quizId
     * @return array
     */
    public function getTransferEffect(int $quizId): array;
}