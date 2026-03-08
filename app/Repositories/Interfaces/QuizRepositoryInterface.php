<?php

namespace App\Repositories\Interfaces;

use App\Models\Quiz;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface QuizRepositoryInterface
{
    public function findById(int $id): ?Quiz;
    
    public function findBySlug(string $slug): ?Quiz;
    
    public function create(array $data): Quiz;
    
    public function update(int $id, array $data): Quiz;
    
    public function delete(int $id): bool;
    
    public function getAllPublished(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    
    public function getWithQuestions(int $id): ?Quiz;
    
    public function getPopularQuizzes(int $limit = 5): array;
    
    public function getQuizzesByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator;
    
    public function updateTotalQuestions(int $quizId): void;
    
    public function getAllForAdmin(int $perPage = 15): LengthAwarePaginator;
    
    public function search(string $query, int $perPage = 15): LengthAwarePaginator;
    
    public function getByDifficulty(string $difficulty, int $perPage = 15): LengthAwarePaginator;
    
    public function getRecentQuizzes(int $limit = 10): Collection;
    
    public function getFeaturedQuizzes(int $limit = 6): Collection;
    
    public function getQuizStats(int $quizId): array;
    
    public function getLowAttemptQuizzes(int $threshold = 10, int $limit = 20): Collection;
    
    public function getQuizzesNeedingReview(): Collection;
    
    public function bulkUpdateStatus(array $quizIds, bool $isPublished): int;
    
    public function getQuizzesByCategories(array $categoryIds, int $perPage = 15): LengthAwarePaginator;
    
    public function getHighSuccessQuizzes(float $minSuccessRate = 80, int $limit = 10): Collection;
    
    public function getQuizzesWithCompletionRates(int $limit = 10): Collection;
    
    public function getRandomQuizzes(int $limit = 5): Collection;
    
    public function getRecommendedForUser(int $userId, int $limit = 5): Collection;
    
    public function getSimilarDifficultyQuizzes(int $userId, int $limit = 5): Collection;
    
    public function getUnattemptedQuizzes(int $userId, int $perPage = 15): LengthAwarePaginator;
    
    public function getQuizzesWithPendingQuestions(): Collection;
    
    public function getPerformanceTrend(int $quizId, int $days = 30): array;
    
    public function getTopPerformingQuizzes(string $period = 'all', int $limit = 10): Collection;
    
    public function getAttemptDistribution(int $quizId, string $interval = 'day', int $limit = 30): array;
    
    public function getAverageScoresByDifficulty(): array;
    
    public function getCountByCategory(): array;
    
    public function getCountByDifficulty(): array;
    
    public function getQuizzesByDateRange(string $startDate, string $endDate): Collection;
    
    public function getMostAttemptedQuizzes(int $limit = 10): Collection;
    
    public function getHighestRatedQuizzes(int $limit = 10, int $minAttempts = 10): Collection;
    
    public function getLowestRatedQuizzes(int $limit = 10, int $minAttempts = 10): Collection;
    
    public function getQuizzesWithFewQuestions(int $minQuestions = 5): Collection;
    
    public function getExpiredQuizzes(): Collection;
    
    public function getScheduledQuizzes(): Collection;
    
    public function getCompletionStats(int $quizId): array;
    
    public function getQuestionDifficultyBreakdown(int $quizId): array;
    
    public function getAverageTimeTaken(int $quizId): float;
    
    public function getPassRate(int $quizId): float;
    
    public function getDropOffRate(int $quizId): float;
    
    public function getQuizzesByTag(string $tag, int $perPage = 15): LengthAwarePaginator;
    
    public function getRelatedQuizzes(int $quizId, int $limit = 5): Collection;
    
    public function getSuggestionsForUser(int $userId, int $limit = 5): Collection;
    
    public function getUpcomingQuizzes(int $limit = 10): Collection;
    
    public function getQuizLeaderboard(int $quizId, int $limit = 10): Collection;
    
    public function getUserRankForQuiz(int $quizId, int $userId): ?int;
    
    public function exportForReporting(array $filters = []): array;
    
    public function getQuizMetadata(int $quizId): array;
    
    public function updateQuizMetadata(int $quizId, array $metadata): bool;
    
    public function getQuizzesByAuthor(int $authorId, int $perPage = 15): LengthAwarePaginator;
    
    public function cloneQuiz(int $quizId, string $newTitle): ?Quiz;
    
    public function getActivityTimeline(int $quizId, int $days = 30): array;
    
    public function getHighEngagementQuizzes(int $limit = 10): Collection;
    
    public function getLowEngagementQuizzes(int $limit = 10): Collection;
    
    public function archiveOldQuizzes(int $daysUnused = 180): int;
    
    public function restoreArchivedQuizzes(array $quizIds): int;
    
    public function getValidationStatus(int $quizId): array;
    
    public function validateIntegrity(int $quizId): array;
    
    public function getTrendingQuizzes(int $limit = 10): Collection;
    
    public function getSeasonalQuizzes(string $season, int $limit = 10): Collection;
    
    public function getQuizzesByDifficultyRange(string $minDifficulty, string $maxDifficulty, int $perPage = 15): LengthAwarePaginator;
    
    public function getCompletionForecast(int $quizId): array;
    
    public function getPopularityIndex(int $quizId): float;
    
    public function getQualityScore(int $quizId): float;
    
    public function getSimilarPerformanceQuizzes(int $quizId, int $limit = 5): Collection;
    
    public function getOptimizationSuggestions(int $quizId): array;
    
    public function getAccessibilityScore(int $quizId): float;
    
    public function getEngagementMetrics(int $quizId): array;
    
    public function getRetentionRate(int $quizId): float;
    
    public function getSharingStats(int $quizId): array;
    
    public function getFeedbackSummary(int $quizId): array;
    
    public function getRevisionHistory(int $quizId): array;
    
    
    public function compareQuizzes(int $quizId, array $otherQuizIds): array;
    
    public function getLearningObjectives(int $quizId): array;
    
    public function updateLearningObjectives(int $quizId, array $objectives): bool;
    
    public function getPrerequisites(int $quizId): array;
    
    public function checkPrerequisites(int $quizId, int $userId): bool;
    
    public function getPathRecommendations(int $userId, string $path, int $limit = 5): Collection;
    
    public function getSkillTags(int $quizId): array;
    
    public function updateSkillTags(int $quizId, array $skills): bool;
    
    public function getQuizzesBySkill(string $skill, int $level, int $perPage = 15): LengthAwarePaginator;
    
    public function getCertificationInfo(int $quizId): ?array;
    
    public function updateCertificationInfo(int $quizId, array $certInfo): bool;
    
    public function getAvailableBadges(int $quizId): array;
    
    public function awardBadge(int $quizId, int $userId, string $badge): bool;
    
    public function getSchedule(int $quizId): ?array;
    
    public function updateSchedule(int $quizId, array $schedule): bool;
    
    public function getUpcomingScheduledQuizzes(int $limit = 10): Collection;
    
    public function getReminders(int $quizId): array;
    
    public function sendReminders(int $quizId): int;
    
    public function getAnnouncement(int $quizId): ?string;
    
    public function updateAnnouncement(int $quizId, string $announcement): bool;
    
    public function getFaqs(int $quizId): array;
    
    public function updateFaqs(int $quizId, array $faqs): bool;
    
    public function getResources(int $quizId): array;
    
    public function addResource(int $quizId, array $resource): bool;
    
    public function removeResource(int $quizId, int $resourceId): bool;
    
    public function getDiscussions(int $quizId, int $perPage = 20): LengthAwarePaginator;
    
    public function addDiscussion(int $quizId, int $userId, string $content);
    
    public function getReviews(int $quizId, int $perPage = 20): LengthAwarePaginator;
    
    public function addReview(int $quizId, int $userId, int $rating, string $comment);
    
    public function getAverageRating(int $quizId): float;
    
    public function getRatingDistribution(int $quizId): array;
    
    public function getReports(int $quizId, string $type = 'all'): array;
    
    public function reportIssue(int $quizId, int $userId, string $issue, string $description);
    
    public function resolveIssue(int $reportId, string $resolution): bool;
    
    public function getAnalyticsExport(int $quizId, array $options = []): array;
    
    public function getPerformanceBenchmarks(int $quizId): array;
    
    public function compareWithBenchmarks(int $quizId): array;
    
    public function getImprovementPlan(int $quizId): array;
    
    public function implementImprovement(int $quizId, string $suggestion): bool;
    
    public function getAbTestResults(int $quizId): array;
    
    public function createAbTest(int $quizId, array $variants);
    
    public function getExperimentResults(int $quizId): array;
    
    public function runExperiment(int $quizId, string $experiment);
    
    public function getPersonalizationRules(int $quizId): array;
    
    public function updatePersonalizationRules(int $quizId, array $rules): bool;
    
    public function getPersonalizedQuiz(int $quizId, int $userId): array;
    
    public function getAdaptivePath(int $quizId, int $userId): array;
    
    public function updateAdaptivePath(int $quizId, int $userId, array $path): bool;
    
    public function getGamificationElements(int $quizId): array;
    
    public function updateGamificationElements(int $quizId, array $elements): bool;
    
    public function getLeaderboardSettings(int $quizId): array;
    
    public function updateLeaderboardSettings(int $quizId, array $settings): bool;
    
    public function getAchievementSettings(int $quizId): array;
    
    public function updateAchievementSettings(int $quizId, array $settings): bool;
    
    public function getRewardSettings(int $quizId): array;
    
    public function updateRewardSettings(int $quizId, array $settings): bool;
    
    public function getNotificationSettings(int $quizId): array;
    
    public function updateNotificationSettings(int $quizId, array $settings): bool;
    
    public function getPrivacySettings(int $quizId): array;
    
    public function updatePrivacySettings(int $quizId, array $settings): bool;
    
    public function getCollaborationSettings(int $quizId): array;
    
    public function updateCollaborationSettings(int $quizId, array $settings): bool;
    
    public function getCollaborators(int $quizId): Collection;
    
    public function addCollaborator(int $quizId, int $userId, string $role): bool;
    
    public function removeCollaborator(int $quizId, int $userId): bool;
    
    public function getVersionHistory(int $quizId): array;
    
    public function createVersion(int $quizId, string $versionName): bool;
    
    public function restoreVersion(int $quizId, string $version): bool;
    
    public function compareVersions(int $quizId, string $version1, string $version2): array;
    
    public function getTranslation(int $quizId, string $language): ?array;
    
    public function addTranslation(int $quizId, string $language, array $translation): bool;
    
    public function updateTranslation(int $quizId, string $language, array $translation): bool;
    
    public function getAvailableLanguages(int $quizId): array;
    
    public function getAccessibilityFeatures(int $quizId): array;
    
    public function updateAccessibilityFeatures(int $quizId, array $features): bool;
    
    public function getComplianceInfo(int $quizId): array;
    
    public function updateComplianceInfo(int $quizId, array $compliance): bool;
    
    public function getAuditLog(int $quizId, int $limit = 100): Collection;
    
    public function logAction(int $quizId, int $userId, string $action, array $details = []): bool;
    
    public function getBackup(int $quizId): ?array;
    
    public function createBackup(int $quizId): bool;
    
    public function restoreFromBackup(int $quizId, string $backupId): bool;
    
    public function getHealthStatus(int $quizId): array;
    
    public function runHealthCheck(int $quizId): array;
    
    public function fixHealthIssues(int $quizId, array $issues): bool;
    
    public function getPerformanceAlerts(int $quizId): array;
    
    public function acknowledgeAlert(int $alertId): bool;
    
    public function getMaintenanceSchedule(int $quizId): ?array;
    
    public function updateMaintenanceSchedule(int $quizId, array $schedule): bool;
    
    public function performMaintenance(int $quizId, string $type): bool;
    
    public function getDeprecationStatus(int $quizId): array;
    
    public function deprecateQuiz(int $quizId, string $reason): bool;
    
    public function undeprecateQuiz(int $quizId): bool;
    
    public function getReplacementSuggestions(int $quizId): Collection;
    
    public function migrateQuizData(int $sourceQuizId, int $targetQuizId): bool;
    
    public function getDataRetentionPolicy(int $quizId): array;
    
    public function updateDataRetentionPolicy(int $quizId, array $policy): bool;
    
    public function purgeOldData(int $quizId, string $dataType, int $daysOld = 365): int;
    
    public function getExportFormats(int $quizId): array;
    
    public function exportQuiz(int $quizId, string $format);
    
    public function importQuiz(string $format, $data): ?Quiz;
    
    public function getQuizTemplate(int $quizId): array;
    
    public function saveAsTemplate(int $quizId, string $templateName): bool;
    
    public function createFromTemplate(string $templateName, array $customizations = []): ?Quiz;
    
    public function getAvailableTemplates(): array;
    
    public function deleteTemplate(string $templateName): bool;
    
    public function getQuizBlueprint(int $quizId): array;
    
    public function generateFromBlueprint(array $blueprint): ?Quiz;
    
    public function validateBlueprint(array $blueprint): array;
    
    public function getScorecard(int $quizId): array;
    
    public function updateScorecard(int $quizId, array $scorecard): bool;
    
    public function getRubric(int $quizId): array;
    
    public function updateRubric(int $quizId, array $rubric): bool;
    
    public function evaluateWithRubric(int $quizId, int $questionId, string $answer): array;
    
    public function getFeedbackForm(int $quizId): array;
    
    public function updateFeedbackForm(int $quizId, array $form): bool;
    
    public function submitFeedback(int $quizId, int $userId, array $feedback): bool;
    
    public function getFeedbackAnalytics(int $quizId): array;
    
    public function getSurvey(int $quizId): array;
    
    public function updateSurvey(int $quizId, array $survey): bool;
    
    public function submitSurvey(int $quizId, int $userId, array $responses): bool;
    
    public function getSurveyAnalytics(int $quizId): array;
    
    public function getPoll(int $quizId): array;
    
    public function updatePoll(int $quizId, array $poll): bool;
    
    public function submitPollVote(int $quizId, int $userId, string $option): bool;
    
    public function getPollResults(int $quizId): array;
    
    public function getCompetition(int $quizId): ?array;
    
    public function createCompetition(int $quizId, array $settings);
    
    public function joinCompetition(int $competitionId, int $userId): bool;
    
    public function getCompetitionLeaderboard(int $competitionId): Collection;
    
    public function getCompetitionResults(int $competitionId): array;
    
    public function getTournament(int $quizId): ?array;
    
    public function createTournament(int $quizId, array $settings);
    
    public function getTournamentBracket(int $tournamentId): array;
    
    public function updateMatchResult(int $matchId, int $winnerId): bool;
    
    public function getTournamentWinner(int $tournamentId): ?array;
}