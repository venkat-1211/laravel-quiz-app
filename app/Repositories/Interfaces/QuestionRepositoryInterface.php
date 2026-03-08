<?php

namespace App\Repositories\Interfaces;

use App\Models\Question;

interface QuestionRepositoryInterface
{
    public function findById(int $id): ?Question;
    
    public function getByQuiz(int $quizId, bool $shuffled = false): array;
    
    public function create(array $data): Question;
    
    public function update(int $id, array $data): Question;
    
    public function delete(int $id): bool;
    
    public function bulkCreate(array $questions): bool;
    
    public function updateOrder(int $quizId, array $order): void;
    
    public function getQuestionsWithStats(int $quizId): array;
    
    public function getDifficultQuestions(int $quizId, float $threshold = 50.0): array;
    
    public function getQuestionsByDifficulty(int $quizId, string $difficulty): array;
    
    public function getQuestionsByType(int $quizId, string $type): array;
    
    public function getRandomQuestions(int $quizId, int $count = 5): array;
    
    public function searchQuestions(string $query, int $perPage = 15): array;
    
    public function getQuestionsNeedingReview(int $quizId): array;
    
    public function getQuestionStats(int $questionId): array;
    
    public function getQuestionUsage(int $questionId): array;
    
    public function getQuestionHistory(int $questionId): array;
    
    public function duplicateQuestion(int $id, ?int $newQuizId = null): ?Question;
    
    public function bulkDuplicate(array $questionIds, int $targetQuizId): int;
    
    public function moveQuestions(array $questionIds, int $targetQuizId): int;
    
    public function getQuestionCountByQuiz(int $quizId): int;
    
    public function getQuestionCountByDifficulty(int $quizId): array;
    
    public function getQuestionCountByType(int $quizId): array;
    
    public function getAverageTimePerQuestion(int $quizId): float;
    
    public function getMostMissedQuestions(int $quizId, int $limit = 10): array;
    
    public function getMostCorrectQuestions(int $quizId, int $limit = 10): array;
    
    public function getQuestionSuccessRate(int $questionId): float;
    
    public function getQuestionTimeAverage(int $questionId): float;
    
    public function getQuestionOptionDistribution(int $questionId): array;
    
    public function getQuestionsWithExplanations(int $quizId): array;
    
    public function getQuestionsWithMedia(int $quizId): array;
    
    public function getQuestionsWithoutExplanations(int $quizId): array;
    
    public function addExplanation(int $questionId, string $explanation): bool;
    
    public function attachMedia(int $questionId, string $type, string $url): bool;
    
    public function removeMedia(int $questionId, string $type): bool;
    
    public function validateQuestions(int $quizId): array;
    
    public function validateQuestionData(array $data): array;
    
    public function validateCorrectAnswer(string $correctAnswer, array $options): bool;
    
    public function getInvalidQuestions(int $quizId): array;
    
    public function fixInvalidQuestions(int $quizId): int;
    
    public function importQuestions(int $quizId, array $questions): array;
    
    public function exportQuestions(int $quizId, string $format = 'json'): array;
    
    public function getQuestionTemplate(int $questionId): array;
    
    public function createFromTemplate(array $template, int $quizId): ?Question;
    
    public function getSimilarQuestions(int $questionId, int $limit = 5): array;
    
    public function getRelatedQuestions(int $questionId, int $limit = 5): array;
    
    public function getQuestionTags(int $questionId): array;
    
    public function addTag(int $questionId, string $tag): bool;
    
    public function removeTag(int $questionId, string $tag): bool;
    
    public function getQuestionsByTag(string $tag, int $perPage = 15): array;
    
    public function getPopularTags(int $limit = 20): array;
    
    public function getQuestionCategories(int $questionId): array;
    
    public function categorizeQuestion(int $questionId, string $category): bool;
    
    public function getQuestionsByCategory(string $category, int $perPage = 15): array;
    
    public function reorderQuestions(int $quizId, array $order): bool;
    
    public function randomizeOrder(int $quizId): bool;
    
    public function resetOrder(int $quizId): bool;
    
    public function getQuestionOrder(int $quizId): array;
    
    public function getNextQuestion(int $currentQuestionId, int $quizId): ?array;
    
    public function getPreviousQuestion(int $currentQuestionId, int $quizId): ?array;
    
    public function archiveQuestions(array $questionIds): int;
    
    public function restoreQuestions(array $questionIds): int;
    
    public function getArchivedQuestions(int $days = 30): array;
    
    public function permanentlyDelete(array $questionIds): int;
    
    public function getQuestionsForReview(int $userId, int $limit = 10): array;
    
    public function getQuestionsForSpacedRepetition(int $userId, int $limit = 10): array;
    
    public function getQuestionsByMasteryLevel(int $userId, string $level, int $limit = 10): array;
    
    public function getRecommendedQuestions(int $userId, int $limit = 10): array;
    
    public function getQuestionsByPerformance(int $userId, string $type = 'weak', int $limit = 10): array;
    
    public function trackQuestionView(int $questionId, int $userId): void;
    
    public function getRecentlyViewed(int $userId, int $limit = 10): array;
    
    public function getPopularQuestions(int $limit = 10): array;
    
    public function getTrendingQuestions(int $days = 7, int $limit = 10): array;
    
    public function getQuestionFeedback(int $questionId): array;
    
    public function addFeedback(int $questionId, int $userId, string $feedback, int $rating = null): bool;
    
    public function reportIssue(int $questionId, int $userId, string $issue, string $description): bool;
    
    public function resolveIssue(int $issueId, string $resolution): bool;
    
    public function getQuestionReports(int $questionId): array;
    
    public function getQuestionDifficulty(int $questionId): float;
    
    public function getQuestionDiscrimination(int $questionId): float;
    
    public function getQuestionGuessability(int $questionId): float;
    
    public function getQuestionReliability(int $questionId): float;
    
    public function getQuestionValidity(int $questionId): float;
    
    public function getQuestionIrtParameters(int $questionId): array;
    
    public function calibrateQuestionDifficulty(int $questionId): float;
    
    public function updateQuestionStatistics(int $questionId): void;
    
    public function recalculateAllStats(int $quizId): void;
    
    public function getQuestionVersion(int $questionId): int;
    
    public function createQuestionVersion(int $questionId): bool;
    
    public function revertToVersion(int $questionId, int $version): bool;
    
    public function compareVersions(int $questionId, int $version1, int $version2): array;
    
    public function getQuestionAuditLog(int $questionId): array;
    
    public function logQuestionAction(int $questionId, int $userId, string $action, array $details = []): bool;
    
    public function getQuestionsByContributor(int $userId, int $perPage = 15): array;
    
    public function getContributorStats(int $userId): array;
    
    public function getTopContributors(int $limit = 10): array;
    
    public function getQuestionCollaborators(int $questionId): array;
    
    public function addCollaborator(int $questionId, int $userId, string $role): bool;
    
    public function removeCollaborator(int $questionId, int $userId): bool;
    
    public function getQuestionComments(int $questionId, int $perPage = 20): array;
    
    public function addComment(int $questionId, int $userId, string $comment): bool;
    
    public function deleteComment(int $commentId): bool;
    
    public function getQuestionNotes(int $questionId): array;
    
    public function addNote(int $questionId, int $userId, string $note): bool;
    
    public function deleteNote(int $noteId): bool;
    
    public function getQuestionResources(int $questionId): array;
    
    public function addResource(int $questionId, string $type, string $url, string $title = null): bool;
    
    public function removeResource(int $questionId, int $resourceId): bool;
    
    public function getRelatedResources(int $questionId): array;
    
    public function getQuestionTips(int $questionId): array;
    
    public function addTip(int $questionId, string $tip, int $userId = null): bool;
    
    public function getQuestionHints(int $questionId): array;
    
    public function addHint(int $questionId, string $hint, int $order = 0): bool;
    
    public function getQuestionSolutions(int $questionId): array;
    
    public function addSolution(int $questionId, string $solution, string $type = 'text'): bool;
    
    public function validateSolution(int $questionId, string $solution): bool;
    
    public function getQuestionExamples(int $questionId): array;
    
    public function addExample(int $questionId, string $example): bool;
    
    public function getQuestionPrerequisites(int $questionId): array;
    
    public function addPrerequisite(int $questionId, int $prerequisiteId): bool;
    
    public function removePrerequisite(int $questionId, int $prerequisiteId): bool;
    
    public function getQuestionLearningObjectives(int $questionId): array;
    
    public function addLearningObjective(int $questionId, string $objective): bool;
    
    public function getQuestionSkills(int $questionId): array;
    
    public function addSkill(int $questionId, string $skill, int $level = 1): bool;
    
    public function getQuestionsBySkill(string $skill, int $level = null, int $perPage = 15): array;
    
    public function getQuestionDifficultyLevel(int $questionId): string;
    
    public function setDifficultyLevel(int $questionId, string $level): bool;
    
    public function autoSetDifficulty(int $questionId): string;
    
    public function getQuestionTimeEstimate(int $questionId): int;
    
    public function setTimeEstimate(int $questionId, int $seconds): bool;
    
    public function getQuestionPoints(int $questionId): int;
    
    public function setPoints(int $questionId, int $points): bool;
    
    public function getQuestionScoringRules(int $questionId): array;
    
    public function setScoringRules(int $questionId, array $rules): bool;
    
    public function getQuestionPenalty(int $questionId): float;
    
    public function setPenalty(int $questionId, float $penalty): bool;
    
    public function getQuestionBonusCriteria(int $questionId): array;
    
    public function setBonusCriteria(int $questionId, array $criteria): bool;
    
    public function getQuestionAchievements(int $questionId): array;
    
    public function attachAchievement(int $questionId, int $achievementId): bool;
    
    public function getQuestionBadges(int $questionId): array;
    
    public function attachBadge(int $questionId, int $badgeId): bool;
    
    public function getQuestionLeaderboard(int $questionId, int $limit = 10): array;
    
    public function getQuestionRankings(int $questionId): array;
    
    public function getUserRankOnQuestion(int $questionId, int $userId): ?array;
    
    public function getQuestionAttempts(int $questionId, int $limit = 100): array;
    
    public function getUserAttemptsOnQuestion(int $questionId, int $userId): array;
    
    public function getQuestionSuccessRateByDemographic(int $questionId, string $demographic): array;
    
    public function getQuestionPerformanceByTime(int $questionId): array;
    
    public function getQuestionPerformanceByLocation(int $questionId): array;
    
    public function getQuestionABTestResults(int $questionId): array;
    
    public function createABTest(int $questionId, array $variants): bool;
    
    public function getQuestionExperiments(int $questionId): array;
    
    public function runExperiment(int $questionId, string $experiment): array;
    
    public function getQuestionPersonalization(int $questionId): array;
    
    public function setPersonalization(int $questionId, array $settings): bool;
    
    public function getPersonalizedQuestion(int $questionId, int $userId): array;
    
    public function getQuestionAccessibility(int $questionId): array;
    
    public function setAccessibility(int $questionId, array $settings): bool;
    
    public function getAlternativeFormats(int $questionId): array;
    
    public function addAlternativeFormat(int $questionId, string $format, string $content): bool;
    
    public function getQuestionTranslations(int $questionId): array;
    
    public function addTranslation(int $questionId, string $language, array $translation): bool;
    
    public function getQuestionsNeedingTranslation(string $language): array;
    
    public function getQuestionCopyright(int $questionId): array;
    
    public function setCopyright(int $questionId, array $copyright): bool;
    
    public function getQuestionLicense(int $questionId): string;
    
    public function setLicense(int $questionId, string $license): bool;
    
    public function getQuestionAttributions(int $questionId): array;
    
    public function addAttribution(int $questionId, string $name, string $role): bool;
    
    public function getQuestionCitations(int $questionId): array;
    
    public function addCitation(int $questionId, string $citation): bool;
    
    public function getQuestionReferences(int $questionId): array;
    
    public function addReference(int $questionId, string $reference): bool;
    
    public function getQuestionSources(int $questionId): array;
    
    public function addSource(int $questionId, string $source): bool;
    
    public function getQuestionDerivations(int $questionId): array;
    
    public function addDerivation(int $questionId, string $derivation): bool;
    
    public function getQuestionPlagiarismScore(int $questionId): float;
    
    public function checkPlagiarism(int $questionId): array;
    
    public function getQuestionOriginality(int $questionId): float;
    
    public function getQuestionUniqueness(int $questionId): float;
    
    public function getQuestionSimilarities(int $questionId): array;
    
    public function mergeQuestions(int $sourceId, int $targetId): bool;
    
    public function splitQuestion(int $questionId, array $parts): array;
    
    public function combineQuestions(array $questionIds): ?Question;
    
    public function getQuestionDependencies(int $questionId): array;
    
    public function addDependency(int $questionId, int $dependsOnId, string $type = 'requires'): bool;
    
    public function removeDependency(int $questionId, int $dependsOnId): bool;
    
    public function getQuestionImpact(int $questionId): array;
    
    public function calculateQuestionImpact(int $questionId): float;
    
    public function getQuestionROI(int $questionId): float;
    
    public function getQuestionValue(int $questionId): float;
    
    public function getQuestionCost(int $questionId): float;
    
    public function getQuestionEfficiency(int $questionId): float;
    
    public function getQuestionEffectiveness(int $questionId): float;
    
    public function getQuestionProductivity(int $questionId): float;
    
    public function getQuestionSatisfaction(int $questionId): float;
    
    public function getQuestionEngagement(int $questionId): float;
    
    public function getQuestionMastery(int $questionId): float;
    
    public function getQuestionLearning(int $questionId): float;
    
    public function getQuestionImprovement(int $questionId): float;
    
    public function getQuestionProgress(int $questionId): float;
    
    public function getQuestionAchievement(int $questionId): float;
    
    public function getQuestionRecognition(int $questionId): float;
    
    public function getQuestionReward(int $questionId): float;
    
    public function getQuestionMotivation(int $questionId): float;
    
    public function getQuestionConfidence(int $questionId): float;
    
    public function getQuestionSelfEfficacy(int $questionId): float;
    
    public function getQuestionAnxiety(int $questionId): float;
    
    public function getQuestionStress(int $questionId): float;
    
    public function getQuestionFatigue(int $questionId): float;
    
    public function getQuestionBoredom(int $questionId): float;
    
    public function getQuestionFrustration(int $questionId): float;
    
    public function getQuestionConfusion(int $questionId): float;
    
    public function getQuestionCuriosity(int $questionId): float;
    
    public function getQuestionInterest(int $questionId): float;
    
    public function getQuestionRelevance(int $questionId): float;
    
    public function getQuestionUsefulness(int $questionId): float;
    
    public function getQuestionApplicability(int $questionId): float;
    
    public function getQuestionTransfer(int $questionId): float;
    
    public function getQuestionPrediction(int $questionId): array;
    
    public function predictPerformance(int $questionId, int $userId): float;
    
    public function getQuestionForecast(int $questionId): array;
    
    public function getQuestionTrends(int $questionId): array;
    
    public function getQuestionSeasonality(int $questionId): array;
    
    public function getQuestionCycles(int $questionId): array;
    
    public function getQuestionPatterns(int $questionId): array;
    
    public function getQuestionAnomalies(int $questionId): array;
    
    public function detectQuestionAnomalies(int $questionId): array;
    
    public function getQuestionOutliers(int $questionId): array;
    
    public function getQuestionClusters(int $questionId): array;
    
    public function getQuestionSegments(int $questionId): array;
    
    public function getQuestionProfiles(int $questionId): array;
    
    public function getQuestionPersonas(int $questionId): array;
    
    public function getQuestionJourneys(int $questionId): array;
    
    public function getQuestionPaths(int $questionId): array;
    
    public function getQuestionFunnels(int $questionId): array;
    
    public function getQuestionConversion(int $questionId): float;
    
    public function getQuestionChurn(int $questionId): float;
    
    public function getQuestionLoyalty(int $questionId): float;
    
    public function getQuestionAdvocacy(int $questionId): float;
    
    public function getQuestionVirality(int $questionId): float;
    
    public function getQuestionNetwork(int $questionId): array;
    
    public function getQuestionConnections(int $questionId): array;
    
    public function getQuestionRelationships(int $questionId): array;
    
    public function getQuestionInteractions(int $questionId): array;
    
    public function getQuestionRatings(int $questionId): array;
    
    public function getQuestionShares(int $questionId): array;
    
    public function getQuestionSaves(int $questionId): array;
    
    public function getQuestionBookmarks(int $questionId): array;
    
    public function getQuestionLikes(int $questionId): array;
    
    public function getQuestionDislikes(int $questionId): array;
    
    public function getQuestionFlags(int $questionId): array;
    
    public function getQuestionSpam(int $questionId): array;
    
    public function getQuestionAbuse(int $questionId): array;
    
    public function getQuestionViolations(int $questionId): array;
    
    public function getQuestionAppeals(int $questionId): array;
    
    public function getQuestionDisputes(int $questionId): array;
    
    public function getQuestionResolutions(int $questionId): array;
    
    public function getQuestionSettlements(int $questionId): array;
    
    public function getQuestionArbitrations(int $questionId): array;
    
    public function getQuestionJudgments(int $questionId): array;
    
    public function getQuestionVerdicts(int $questionId): array;
    
    public function getQuestionDecisions(int $questionId): array;
    
    public function getQuestionRulings(int $questionId): array;
    
    public function getQuestionPrecedents(int $questionId): array;
    
    public function getQuestionPolicies(int $questionId): array;
    
    public function getQuestionGuidelines(int $questionId): array;
    
    public function getQuestionStandards(int $questionId): array;
    
    public function getQuestionRegulations(int $questionId): array;
    
    public function getQuestionCompliance(int $questionId): array;
    
    public function getQuestionCertifications(int $questionId): array;
    
    public function getQuestionAccreditations(int $questionId): array;
    
    public function getQuestionEndorsements(int $questionId): array;
    
    public function getQuestionRecommendations(int $questionId): array;
    
    public function getQuestionTestimonials(int $questionId): array;
    
    public function getQuestionCaseStudies(int $questionId): array;
    
    public function getQuestionSuccessStories(int $questionId): array;
    
    public function getQuestionUseCases(int $questionId): array;
    
    public function getQuestionApplications(int $questionId): array;
    
    public function getQuestionImplementations(int $questionId): array;
    
    public function getQuestionIntegrations(int $questionId): array;
    
    public function getQuestionExtensions(int $questionId): array;
    
    public function getQuestionPlugins(int $questionId): array;
    
    public function getQuestionAddons(int $questionId): array;
    
    public function getQuestionModules(int $questionId): array;
    
    public function getQuestionComponents(int $questionId): array;
    
    public function getQuestionFragments(int $questionId): array;
    
    public function getQuestionSnippets(int $questionId): array;
    
    public function getQuestionTemplates(int $questionId): array;
    
    public function getQuestionBlueprints(int $questionId): array;
    
    public function getQuestionSchemas(int $questionId): array;
    
    public function getQuestionModels(int $questionId): array;
    
    public function getQuestionFrameworks(int $questionId): array;
    
    public function getQuestionLibraries(int $questionId): array;
    
    public function getQuestionPackages(int $questionId): array;
    
    public function getQuestionRequirements(int $questionId): array;
    
    public function getQuestionCorequisites(int $questionId): array;
    
    public function getQuestionPostrequisites(int $questionId): array;
    
    public function getQuestionAntecedents(int $questionId): array;
    
    public function getQuestionConsequents(int $questionId): array;
    
    public function getQuestionCauses(int $questionId): array;
    
    public function getQuestionEffects(int $questionId): array;
    
    public function getQuestionCorrelates(int $questionId): array;
    
    public function getQuestionPredictors(int $questionId): array;
    
    public function getQuestionOutcomes(int $questionId): array;
    
    public function getQuestionResults(int $questionId): array;
    
    public function getQuestionOutputs(int $questionId): array;
    
    public function getQuestionDeliverables(int $questionId): array;
    
    public function getQuestionArtifacts(int $questionId): array;
    
    public function getQuestionEvidence(int $questionId): array;
    
    public function getQuestionProof(int $questionId): array;
    
    public function getQuestionValidation(int $questionId): array;
    
    public function getQuestionVerification(int $questionId): array;
    
    public function getQuestionAuthentication(int $questionId): array;
    
    public function getQuestionAuthorization(int $questionId): array;
    
    public function getQuestionPermissions(int $questionId): array;
    
    public function getQuestionRoles(int $questionId): array;
    
    public function getQuestionAccess(int $questionId): array;
    
    public function getQuestionSecurity(int $questionId): array;
    
    public function getQuestionPrivacy(int $questionId): array;
    
    public function getQuestionConfidentiality(int $questionId): array;
    
    public function getQuestionIntegrity(int $questionId): array;
    
    public function getQuestionAvailability(int $questionId): array;
    
    public function getQuestionDurability(int $questionId): array;
    
    public function getQuestionScalability(int $questionId): array;
    
    public function getQuestionPerformance(int $questionId): array;
    
    public function getQuestionExcellence(int $questionId): array;
    
    public function getQuestionExpertise(int $questionId): array;
    
    public function getQuestionProficiency(int $questionId): array;
    
    public function getQuestionCompetence(int $questionId): array;
    
    public function getQuestionCapability(int $questionId): array;
    
    public function getQuestionCapacity(int $questionId): array;
    
    public function getQuestionPotential(int $questionId): array;
    
    public function getQuestionGrowth(int $questionId): array;
    
    public function getQuestionDevelopment(int $questionId): array;
    
    public function getQuestionEvolution(int $questionId): array;
    
    public function getQuestionTransformation(int $questionId): array;
    
    public function getQuestionInnovation(int $questionId): array;
    
    public function getQuestionDisruption(int $questionId): array;
    
    public function getQuestionRevolution(int $questionId): array;
    
    public function getQuestionParadigm(int $questionId): array;
    
    public function getQuestionShift(int $questionId): array;
    
    public function getQuestionChange(int $questionId): array;
    
    public function getQuestionAdvancement(int $questionId): array;
    
    public function getQuestionEnhancement(int $questionId): array;
    
    public function getQuestionOptimization(int $questionId): array;
    
    public function getQuestionRefinement(int $questionId): array;
    
    public function getQuestionRevision(int $questionId): array;
    
    public function getQuestionUpdate(int $questionId): array;
    
    public function getQuestionUpgrade(int $questionId): array;
    
    public function getQuestionMigration(int $questionId): array;
    
    public function getQuestionLocalization(int $questionId): array;
    
    public function getQuestionInternationalization(int $questionId): array;
    
    public function getQuestionGlobalization(int $questionId): array;
    
    public function getQuestionStandardization(int $questionId): array;
    
    public function getQuestionCustomization(int $questionId): array;
    
    public function getQuestionIndividualization(int $questionId): array;
    
    public function getQuestionAdaptation(int $questionId): array;
    
    public function getQuestionModification(int $questionId): array;
    
    public function getQuestionAdjustment(int $questionId): array;
    
    public function getQuestionCorrection(int $questionId): array;
    
    public function getQuestionRectification(int $questionId): array;
    
    public function getQuestionRemediation(int $questionId): array;
    
    public function getQuestionIntervention(int $questionId): array;
    
    public function getQuestionSupport(int $questionId): array;
    
    public function getQuestionAssistance(int $questionId): array;
    
    public function getQuestionGuidance(int $questionId): array;
    
    public function getQuestionMentoring(int $questionId): array;
    
    public function getQuestionCoaching(int $questionId): array;
    
    public function getQuestionTutoring(int $questionId): array;
    
    public function getQuestionTeaching(int $questionId): array;
    
    public function getQuestionInstruction(int $questionId): array;
    
    public function getQuestionEducation(int $questionId): array;
    
    public function getQuestionTraining(int $questionId): array;
}