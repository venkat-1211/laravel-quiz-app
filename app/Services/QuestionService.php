<?php

namespace App\Services;

use App\DTOs\QuestionDTO;
use App\Models\Question;
use App\Services\Interfaces\QuestionServiceInterface;
use App\Repositories\Interfaces\QuestionRepositoryInterface;
use App\Repositories\Interfaces\QuizRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\UploadedFile;
use App\Imports\QuestionsImport;
use Maatwebsite\Excel\Facades\Excel;

class QuestionService implements QuestionServiceInterface
{
    public function __construct(
        private QuestionRepositoryInterface $questionRepository,
        private QuizRepositoryInterface $quizRepository
    ) {}

    /**
     * Get question by ID.
     *
     * @param int $id
     * @return QuestionDTO|null
     */
    public function getQuestionById(int $id): ?QuestionDTO
    {
        $question = $this->questionRepository->findById($id);
        
        if (!$question) {
            return null;
        }
        
        return QuestionDTO::fromModel($question);
    }

    /**
     * Get questions by quiz ID.
     *
     * @param int $quizId
     * @param bool $shuffled
     * @return array
     */
    public function getQuestionsByQuiz(int $quizId, bool $shuffled = false): array
    {
        $questions = $this->questionRepository->getByQuiz($quizId, $shuffled);
        
        return array_map(function ($question) {
            return [
                'id' => $question['id'],
                'question_text' => $question['question_text'],
                'options' => $question['options'],
                'points' => $question['points'],
                'difficulty' => $question['difficulty'],
                'order' => $question['order'],
            ];
        }, $questions);
    }

    /**
     * Create a new question.
     *
     * @param array $data
     * @return QuestionDTO
     */
    public function createQuestion(array $data): QuestionDTO
    {
        DB::beginTransaction();
        
        try {
            // Get the current max order
            $maxOrder = DB::table('questions')
                ->where('quiz_id', $data['quiz_id'])
                ->max('order') ?? 0;
            
            $data['order'] = $maxOrder + 1;
            
            $question = $this->questionRepository->create($data);
            
            // Clear relevant cache
            $this->clearCache($data['quiz_id']);
            
            DB::commit();
            
            return QuestionDTO::fromModel($question);
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to create question: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing question.
     *
     * @param int $id
     * @param array $data
     * @return QuestionDTO
     */
    public function updateQuestion(int $id, array $data): QuestionDTO
    {
        DB::beginTransaction();
        
        try {
            $oldQuestion = $this->questionRepository->findById($id);
            
            if (!$oldQuestion) {
                throw new \Exception('Question not found');
            }
            
            $question = $this->questionRepository->update($id, $data);
            
            // Clear cache for both old and new quiz if quiz_id changed
            $this->clearCache($oldQuestion->quiz_id);
            
            if (isset($data['quiz_id']) && $data['quiz_id'] != $oldQuestion->quiz_id) {
                $this->clearCache($data['quiz_id']);
            }
            
            DB::commit();
            
            return QuestionDTO::fromModel($question);
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to update question: ' . $e->getMessage());
        }
    }

    /**
     * Delete a question.
     *
     * @param int $id
     * @return bool
     */
    public function deleteQuestion(int $id): bool
    {
        DB::beginTransaction();
        
        try {
            $question = $this->questionRepository->findById($id);
            
            if (!$question) {
                throw new \Exception('Question not found');
            }
            
            $quizId = $question->quiz_id;
            $result = $this->questionRepository->delete($id);
            
            // Reorder remaining questions
            $this->reorderAfterDelete($quizId);
            
            DB::commit();
            
            return $result;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to delete question: ' . $e->getMessage());
        }
    }

    /**
     * Bulk create questions.
     *
     * @param array $questions
     * @return bool
     */
    public function bulkCreateQuestions(array $questions): bool
    {
        if (empty($questions)) {
            throw new \Exception('No questions provided');
        }
        
        DB::beginTransaction();
        
        try {
            $quizId = $questions[0]['quiz_id'];
            
            // Get current max order
            $maxOrder = DB::table('questions')
                ->where('quiz_id', $quizId)
                ->max('order') ?? 0;
            
            // Add order to each question
            foreach ($questions as $index => &$question) {
                $question['order'] = $maxOrder + $index + 1;
            }
            
            $result = $this->questionRepository->bulkCreate($questions);
            
            DB::commit();
            
            return $result;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to bulk create questions: ' . $e->getMessage());
        }
    }

    /**
     * Bulk upload questions from CSV.
     *
     * @param int $quizId
     * @param UploadedFile $file
     * @return array Import results
     */
    public function bulkUploadFromCsv(int $quizId, \Illuminate\Http\UploadedFile $file): array
    {
        try {
            $import = new QuestionsImport($quizId);
            Excel::import($import, $file);
            
            $errors = $import->getErrors();
            $successCount = $import->getSuccessCount();
            
            if (!empty($errors)) {
                return [
                    'success' => false,
                    'message' => 'Some questions failed to import',
                    'errors' => $errors,
                    'success_count' => $successCount,
                ];
            }
            
            $this->clearCache($quizId);
            
            return [
                'success' => true,
                'message' => "Successfully imported {$successCount} questions",
                'success_count' => $successCount,
            ];
            
        } catch (\Exception $e) {
            throw new \Exception('Failed to upload CSV: ' . $e->getMessage());
        }
    }

    /**
     * Update question order.
     *
     * @param int $quizId
     * @param array $order
     * @return bool
     */
    public function updateOrder(int $quizId, array $order): bool
    {
        DB::beginTransaction();
        
        try {
            foreach ($order as $item) {
                DB::table('questions')
                    ->where('id', $item['id'])
                    ->where('quiz_id', $quizId)
                    ->update(['order' => $item['order']]);
            }
            
            $this->clearCache($quizId);
            
            DB::commit();
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to update order: ' . $e->getMessage());
        }
    }

    /**
     * Get question statistics.
     *
     * @param int $questionId
     * @return array
     */
    public function getQuestionStats(int $questionId): array
    {
        return Cache::remember("question.stats.{$questionId}", 3600, function () use ($questionId) {
            $stats = DB::table('attempt_answers')
                ->join('attempts', 'attempt_answers.attempt_id', '=', 'attempts.id')
                ->where('attempt_answers.question_id', $questionId)
                ->where('attempts.status', 'completed')
                ->select(
                    DB::raw('COUNT(*) as total_attempts'),
                    DB::raw('SUM(CASE WHEN attempt_answers.is_correct = 1 THEN 1 ELSE 0 END) as correct_count'),
                    DB::raw('AVG(attempt_answers.time_spent) as avg_time_spent'),
                    DB::raw('COUNT(DISTINCT attempts.user_id) as unique_users')
                )
                ->first();

            $optionDistribution = DB::table('attempt_answers')
                ->join('attempts', 'attempt_answers.attempt_id', '=', 'attempts.id')
                ->where('attempt_answers.question_id', $questionId)
                ->where('attempts.status', 'completed')
                ->whereNotNull('attempt_answers.selected_answer')
                ->select('selected_answer', DB::raw('COUNT(*) as count'))
                ->groupBy('selected_answer')
                ->get()
                ->pluck('count', 'selected_answer')
                ->toArray();

            return [
                'total_attempts' => (int) ($stats->total_attempts ?? 0),
                'correct_count' => (int) ($stats->correct_count ?? 0),
                'incorrect_count' => (int) (($stats->total_attempts ?? 0) - ($stats->correct_count ?? 0)),
                'success_rate' => $stats->total_attempts > 0 
                    ? round(($stats->correct_count / $stats->total_attempts) * 100, 2)
                    : 0,
                'avg_time_spent' => round($stats->avg_time_spent ?? 0, 2),
                'unique_users' => (int) ($stats->unique_users ?? 0),
                'option_distribution' => $optionDistribution,
            ];
        });
    }

    /**
     * Validate question data.
     *
     * @param array $data
     * @return array Validation errors
     */
    public function validateQuestion(array $data): array
    {
        $validator = Validator::make($data, [
            'quiz_id' => 'required|exists:quizzes,id',
            'question_text' => 'required|string|max:1000',
            'options' => 'required|array|min:2|max:6',
            'options.*' => 'required|string|max:255',
            'correct_answer' => 'required|string|in:A,B,C,D,E,F',
            'explanation' => 'nullable|string|max:1000',
            'image_url' => 'nullable|url',
            'video_url' => 'nullable|url',
            'difficulty' => 'required|in:easy,medium,hard',
            'points' => 'required|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $validator->errors()->toArray();
        }

        return [];
    }

    /**
     * Duplicate a question.
     *
     * @param int $id
     * @param int|null $newQuizId
     * @return QuestionDTO
     */
    public function duplicateQuestion(int $id, ?int $newQuizId = null): QuestionDTO
    {
        DB::beginTransaction();
        
        try {
            $question = $this->questionRepository->findById($id);
            
            if (!$question) {
                throw new \Exception('Question not found');
            }
            
            $newData = $question->toArray();
            unset($newData['id'], $newData['created_at'], $newData['updated_at']);
            
            if ($newQuizId) {
                $newData['quiz_id'] = $newQuizId;
            }
            
            // Add "Copy" to question text
            $newData['question_text'] = $newData['question_text'] . ' (Copy)';
            
            // Get new order
            $maxOrder = DB::table('questions')
                ->where('quiz_id', $newData['quiz_id'])
                ->max('order') ?? 0;
            
            $newData['order'] = $maxOrder + 1;
            
            $newQuestion = $this->questionRepository->create($newData);
            
            DB::commit();
            
            return QuestionDTO::fromModel($newQuestion);
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to duplicate question: ' . $e->getMessage());
        }
    }

    /**
     * Import questions from another quiz.
     *
     * @param int $sourceQuizId
     * @param int $targetQuizId
     * @param array $questionIds
     * @return int Number of imported questions
     */
    public function importFromQuiz(int $sourceQuizId, int $targetQuizId, array $questionIds): int
    {
        DB::beginTransaction();
        
        try {
            $sourceQuestions = DB::table('questions')
                ->where('quiz_id', $sourceQuizId)
                ->whereIn('id', $questionIds)
                ->get();
            
            if ($sourceQuestions->isEmpty()) {
                throw new \Exception('No questions found to import');
            }
            
            // Get current max order for target quiz
            $maxOrder = DB::table('questions')
                ->where('quiz_id', $targetQuizId)
                ->max('order') ?? 0;
            
            $imported = 0;
            
            foreach ($sourceQuestions as $index => $question) {
                $newData = (array) $question;
                unset($newData['id'], $newData['created_at'], $newData['updated_at']);
                $newData['quiz_id'] = $targetQuizId;
                $newData['order'] = $maxOrder + $index + 1;
                
                DB::table('questions')->insert($newData);
                $imported++;
            }
            
            // Update total questions count for target quiz
            $this->quizRepository->updateTotalQuestions($targetQuizId);
            
            DB::commit();
            
            $this->clearCache($targetQuizId);
            
            return $imported;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to import questions: ' . $e->getMessage());
        }
    }

    /**
     * Get difficult questions analysis.
     *
     * @param int $quizId
     * @return array
     */
    public function getDifficultQuestionsAnalysis(int $quizId): array
    {
        return Cache::remember("quiz.difficult_questions.{$quizId}", 3600, function () use ($quizId) {
            $questions = DB::table('questions')
                ->where('quiz_id', $quizId)
                ->get();
            
            $analysis = [];
            
            foreach ($questions as $question) {
                $stats = $this->getQuestionStats($question->id);
                
                if ($stats['total_attempts'] > 0 && $stats['success_rate'] < 50) {
                    $analysis[] = [
                        'id' => $question->id,
                        'question_text' => $question->question_text,
                        'success_rate' => $stats['success_rate'],
                        'total_attempts' => $stats['total_attempts'],
                        'avg_time_spent' => $stats['avg_time_spent'],
                        'difficulty' => $question->difficulty,
                    ];
                }
            }
            
            // Sort by success rate (lowest first)
            usort($analysis, function ($a, $b) {
                return $a['success_rate'] <=> $b['success_rate'];
            });
            
            return $analysis;
        });
    }

    /**
     * Export questions to array.
     *
     * @param int $quizId
     * @return array
     */
    public function exportQuestions(int $quizId): array
    {
        $questions = DB::table('questions')
            ->where('quiz_id', $quizId)
            ->orderBy('order')
            ->get();
        
        $export = [];
        
        foreach ($questions as $question) {
            $options = json_decode($question->options, true);
            
            $export[] = [
                'question_text' => $question->question_text,
                'option_a' => $options['A'] ?? '',
                'option_b' => $options['B'] ?? '',
                'option_c' => $options['C'] ?? '',
                'option_d' => $options['D'] ?? '',
                'correct_answer' => $question->correct_answer,
                'explanation' => $question->explanation,
                'difficulty' => $question->difficulty,
                'points' => $question->points,
            ];
        }
        
        return $export;
    }

    /**
     * Validate correct answer format.
     *
     * @param string $correctAnswer
     * @param array $options
     * @return bool
     */
    public function validateCorrectAnswer(string $correctAnswer, array $options): bool
    {
        return isset($options[$correctAnswer]) && !empty($options[$correctAnswer]);
    }

    /**
     * Get questions by difficulty.
     *
     * @param int $quizId
     * @param string $difficulty
     * @return array
     */
    public function getQuestionsByDifficulty(int $quizId, string $difficulty): array
    {
        return Cache::remember("quiz.questions.{$quizId}.difficulty.{$difficulty}", 3600, function () use ($quizId, $difficulty) {
            $questions = DB::table('questions')
                ->where('quiz_id', $quizId)
                ->where('difficulty', $difficulty)
                ->orderBy('order')
                ->get()
                ->toArray();
            
            return array_map(function ($question) {
                return [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'options' => json_decode($question->options, true),
                    'points' => $question->points,
                ];
            }, $questions);
        });
    }

    /**
     * Randomize question order.
     *
     * @param int $quizId
     * @return bool
     */
    public function randomizeOrder(int $quizId): bool
    {
        DB::beginTransaction();
        
        try {
            $questions = DB::table('questions')
                ->where('quiz_id', $quizId)
                ->orderBy('order')
                ->get();
            
            $orders = range(1, $questions->count());
            shuffle($orders);
            
            foreach ($questions as $index => $question) {
                DB::table('questions')
                    ->where('id', $question->id)
                    ->update(['order' => $orders[$index]]);
            }
            
            $this->clearCache($quizId);
            
            DB::commit();
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to randomize order: ' . $e->getMessage());
        }
    }

    /**
     * Get question performance metrics.
     *
     * @param int $questionId
     * @return array
     */
    public function getPerformanceMetrics(int $questionId): array
    {
        $stats = $this->getQuestionStats($questionId);
        
        // Get time-based metrics
        $timeMetrics = DB::table('attempt_answers')
            ->join('attempts', 'attempt_answers.attempt_id', '=', 'attempts.id')
            ->where('attempt_answers.question_id', $questionId)
            ->where('attempts.status', 'completed')
            ->select(
                DB::raw('MIN(attempt_answers.time_spent) as min_time'),
                DB::raw('MAX(attempt_answers.time_spent) as max_time'),
                DB::raw('PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY attempt_answers.time_spent) as median_time')
            )
            ->first();
        
        return array_merge($stats, [
            'min_time' => (int) ($timeMetrics->min_time ?? 0),
            'max_time' => (int) ($timeMetrics->max_time ?? 0),
            'median_time' => (int) ($timeMetrics->median_time ?? 0),
        ]);
    }

    /**
     * Archive old questions.
     *
     * @param int $daysUnused
     * @return int Number of archived questions
     */
    public function archiveOldQuestions(int $daysUnused = 90): int
    {
        $cutoffDate = now()->subDays($daysUnused);
        
        $questions = DB::table('questions')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('attempt_answers')
                    ->whereRaw('attempt_answers.question_id = questions.id')
                    ->where('created_at', '>=', $cutoffDate);
            })
            ->where('created_at', '<', $cutoffDate)
            ->get();
        
        if ($questions->isEmpty()) {
            return 0;
        }
        
        DB::beginTransaction();
        
        try {
            foreach ($questions as $question) {
                // Move to archive table or soft delete
                DB::table('questions_archive')->insert((array) $question);
                DB::table('questions')->where('id', $question->id)->delete();
            }
            
            DB::commit();
            
            return $questions->count();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to archive questions: ' . $e->getMessage());
        }
    }

    /**
     * Restore archived questions.
     *
     * @param array $questionIds
     * @return int Number of restored questions
     */
    public function restoreQuestions(array $questionIds): int
    {
        if (empty($questionIds)) {
            return 0;
        }
        
        DB::beginTransaction();
        
        try {
            $archivedQuestions = DB::table('questions_archive')
                ->whereIn('id', $questionIds)
                ->get();
            
            foreach ($archivedQuestions as $question) {
                $data = (array) $question;
                unset($data['id']);
                
                DB::table('questions')->insert($data);
                DB::table('questions_archive')->where('id', $question->id)->delete();
            }
            
            DB::commit();
            
            return $archivedQuestions->count();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to restore questions: ' . $e->getMessage());
        }
    }

    /**
     * Search questions.
     *
     * @param string $query
     * @param int $perPage
     * @return array
     */
    public function searchQuestions(string $query, int $perPage = 15): array
    {
        $questions = DB::table('questions')
            ->join('quizzes', 'questions.quiz_id', '=', 'quizzes.id')
            ->where('questions.question_text', 'LIKE', "%{$query}%")
            ->orWhere('questions.explanation', 'LIKE', "%{$query}%")
            ->orWhere('quizzes.title', 'LIKE', "%{$query}%")
            ->select(
                'questions.*',
                'quizzes.title as quiz_title',
                'quizzes.slug as quiz_slug'
            )
            ->orderBy('questions.created_at', 'desc')
            ->paginate($perPage);
        
        return [
            'data' => $questions->items(),
            'total' => $questions->total(),
            'per_page' => $questions->perPage(),
            'current_page' => $questions->currentPage(),
            'last_page' => $questions->lastPage(),
        ];
    }

    /**
     * Get question tags/categories.
     *
     * @param int $questionId
     * @return array
     */
    public function getQuestionTags(int $questionId): array
    {
        // This could be expanded if you add a tagging system
        return Cache::remember("question.tags.{$questionId}", 86400, function () use ($questionId) {
            $question = DB::table('questions')->find($questionId);
            
            if (!$question) {
                return [];
            }
            
            $tags = [];
            
            // Add difficulty as a tag
            $tags[] = $question->difficulty;
            
            // You could add more logic here to extract keywords from question text
            // or use a proper tagging system
            
            return $tags;
        });
    }

    /**
     * Add explanation to question.
     *
     * @param int $questionId
     * @param string $explanation
     * @return bool
     */
    public function addExplanation(int $questionId, string $explanation): bool
    {
        $updated = DB::table('questions')
            ->where('id', $questionId)
            ->update(['explanation' => $explanation]);
        
        if ($updated) {
            $question = DB::table('questions')->find($questionId);
            $this->clearCache($question->quiz_id);
        }
        
        return (bool) $updated;
    }

    /**
     * Attach media to question.
     *
     * @param int $questionId
     * @param string $type (image|video|audio)
     * @param string $url
     * @return bool
     */
    public function attachMedia(int $questionId, string $type, string $url): bool
    {
        $field = $type . '_url';
        
        if (!in_array($field, ['image_url', 'video_url', 'audio_url'])) {
            throw new \Exception('Invalid media type');
        }
        
        $updated = DB::table('questions')
            ->where('id', $questionId)
            ->update([$field => $url]);
        
        if ($updated) {
            $question = DB::table('questions')->find($questionId);
            $this->clearCache($question->quiz_id);
        }
        
        return (bool) $updated;
    }

    /**
     * Get similar questions.
     *
     * @param int $questionId
     * @param int $limit
     * @return array
     */
    public function getSimilarQuestions(int $questionId, int $limit = 5): array
    {
        $question = DB::table('questions')->find($questionId);
        
        if (!$question) {
            return [];
        }
        
        // Extract keywords from question text
        $keywords = $this->extractKeywords($question->question_text);
        
        $similar = DB::table('questions')
            ->where('id', '!=', $questionId)
            ->where('quiz_id', $question->quiz_id)
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $query->orWhere('question_text', 'LIKE', "%{$keyword}%");
                }
            })
            ->limit($limit)
            ->get();
        
        return $similar->toArray();
    }

    /**
     * Validate question difficulty consistency.
     *
     * @param int $quizId
     * @return array
     */
    public function validateDifficultyConsistency(int $quizId): array
    {
        $questions = DB::table('questions')
            ->where('quiz_id', $quizId)
            ->get();
        
        $difficultyCounts = [
            'easy' => 0,
            'medium' => 0,
            'hard' => 0,
        ];
        
        foreach ($questions as $question) {
            $difficultyCounts[$question->difficulty]++;
        }
        
        $total = $questions->count();
        
        return [
            'total' => $total,
            'distribution' => $difficultyCounts,
            'percentages' => [
                'easy' => $total > 0 ? round(($difficultyCounts['easy'] / $total) * 100, 2) : 0,
                'medium' => $total > 0 ? round(($difficultyCounts['medium'] / $total) * 100, 2) : 0,
                'hard' => $total > 0 ? round(($difficultyCounts['hard'] / $total) * 100, 2) : 0,
            ],
            'is_balanced' => $this->isDifficultyBalanced($difficultyCounts, $total),
            'recommendations' => $this->getDifficultyRecommendations($difficultyCounts, $total),
        ];
    }

    /**
     * Bulk delete questions.
     *
     * @param array $questionIds
     * @return int Number of deleted questions
     */
    public function bulkDelete(array $questionIds): int
    {
        if (empty($questionIds)) {
            return 0;
        }
        
        DB::beginTransaction();
        
        try {
            // Get quiz IDs for cache clearing
            $quizIds = DB::table('questions')
                ->whereIn('id', $questionIds)
                ->pluck('quiz_id')
                ->unique()
                ->toArray();
            
            $deleted = DB::table('questions')
                ->whereIn('id', $questionIds)
                ->delete();
            
            // Update total questions count for affected quizzes
            foreach ($quizIds as $quizId) {
                $this->quizRepository->updateTotalQuestions($quizId);
                $this->clearCache($quizId);
            }
            
            DB::commit();
            
            return $deleted;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to bulk delete questions: ' . $e->getMessage());
        }
    }

    public function bulkDuplicate(array $questionIds, int $targetQuizId): int
    {
        DB::beginTransaction();
        
        try {
            $questions = Question::whereIn('id', $questionIds)->get(); // Use Question model with proper namespace
            $maxOrder = Question::where('quiz_id', $targetQuizId)->max('order') ?? 0;
            
            $count = 0;
            foreach ($questions as $index => $question) {
                $newData = $question->toArray();
                unset($newData['id'], $newData['created_at'], $newData['updated_at']);
                $newData['quiz_id'] = $targetQuizId;
                $newData['order'] = $maxOrder + $index + 1;
                $newData['question_text'] = $newData['question_text'] . ' (Copy)';
                
                Question::create($newData);
                $count++;
            }
            
            $this->quizRepository->updateTotalQuestions($targetQuizId);
            $this->clearCache($targetQuizId);
            
            DB::commit();
            return $count;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to bulk duplicate questions: ' . $e->getMessage());
        }
    }

    /**
     * Get question usage statistics.
     *
     * @param int $questionId
     * @return array
     */
    public function getUsageStats(int $questionId): array
    {
        return Cache::remember("question.usage.{$questionId}", 3600, function () use ($questionId) {
            $attempts = DB::table('attempt_answers')
                ->where('question_id', $questionId)
                ->count();
            
            $uniqueUsers = DB::table('attempt_answers')
                ->join('attempts', 'attempt_answers.attempt_id', '=', 'attempts.id')
                ->where('attempt_answers.question_id', $questionId)
                ->distinct('attempts.user_id')
                ->count('attempts.user_id');
            
            $lastUsed = DB::table('attempt_answers')
                ->join('attempts', 'attempt_answers.attempt_id', '=', 'attempts.id')
                ->where('attempt_answers.question_id', $questionId)
                ->max('attempts.created_at');
            
            return [
                'total_attempts' => $attempts,
                'unique_users' => $uniqueUsers,
                'last_used' => $lastUsed,
                'times_used_in_quizzes' => 1, // This would need a proper relationship
            ];
        });
    }

    /**
     * Convert question to different format.
     *
     * @param int $questionId
     * @param string $format (text|image|video)
     * @return bool
     */
    public function convertFormat(int $questionId, string $format): bool
    {
        // This is a placeholder for future functionality
        // You would implement actual conversion logic here
        return true;
    }

    /**
     * Get questions needing review.
     *
     * @param int $quizId
     * @return array
     */
    public function getQuestionsNeedingReview(int $quizId): array
    {
        $questions = DB::table('questions')
            ->where('quiz_id', $quizId)
            ->get();
        
        $needingReview = [];
        
        foreach ($questions as $question) {
            $stats = $this->getQuestionStats($question->id);
            
            // Criteria for review:
            // 1. Success rate < 30% (too difficult)
            // 2. Success rate > 95% with many attempts (too easy)
            // 3. High average time (confusing)
            // 4. No attempts yet
            
            if ($stats['total_attempts'] > 0) {
                if ($stats['success_rate'] < 30) {
                    $needingReview[] = [
                        'id' => $question->id,
                        'text' => $question->question_text,
                        'reason' => 'Very difficult (success rate: ' . $stats['success_rate'] . '%)',
                        'stats' => $stats,
                    ];
                } elseif ($stats['success_rate'] > 95 && $stats['total_attempts'] > 50) {
                    $needingReview[] = [
                        'id' => $question->id,
                        'text' => $question->question_text,
                        'reason' => 'Very easy (success rate: ' . $stats['success_rate'] . '%)',
                        'stats' => $stats,
                    ];
                } elseif ($stats['avg_time_spent'] > 60) { // More than 60 seconds
                    $needingReview[] = [
                        'id' => $question->id,
                        'text' => $question->question_text,
                        'reason' => 'Takes too long (avg: ' . $stats['avg_time_spent'] . ' seconds)',
                        'stats' => $stats,
                    ];
                }
            } else {
                $needingReview[] = [
                    'id' => $question->id,
                    'text' => $question->question_text,
                    'reason' => 'Never attempted',
                    'stats' => $stats,
                ];
            }
        }
        
        return $needingReview;
    }

    /**
     * Reorder questions after delete.
     *
     * @param int $quizId
     * @return void
     */
    private function reorderAfterDelete(int $quizId): void
    {
        $questions = DB::table('questions')
            ->where('quiz_id', $quizId)
            ->orderBy('order')
            ->get();
        
        foreach ($questions as $index => $question) {
            DB::table('questions')
                ->where('id', $question->id)
                ->update(['order' => $index + 1]);
        }
        
        $this->clearCache($quizId);
    }

    /**
     * Clear cache for a quiz.
     *
     * @param int $quizId
     * @return void
     */
    private function clearCache(int $quizId): void
    {
        Cache::forget("quiz.questions.{$quizId}");
        Cache::forget("quiz.questions.{$quizId}.easy");
        Cache::forget("quiz.questions.{$quizId}.medium");
        Cache::forget("quiz.questions.{$quizId}.hard");
        Cache::forget("quiz.difficult_questions.{$quizId}");
        Cache::forget("queries.quizzes.published.*");
    }

    /**
     * Extract keywords from text.
     *
     * @param string $text
     * @return array
     */
    private function extractKeywords(string $text): array
    {
        // Remove punctuation and convert to lowercase
        $text = strtolower(preg_replace('/[^\w\s]/', '', $text));
        
        // Split into words
        $words = explode(' ', $text);
        
        // Remove common words
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        
        $keywords = array_filter($words, function ($word) use ($stopWords) {
            return strlen($word) > 3 && !in_array($word, $stopWords);
        });
        
        return array_slice(array_values($keywords), 0, 5);
    }

    /**
     * Check if difficulty distribution is balanced.
     *
     * @param array $counts
     * @param int $total
     * @return bool
     */
    private function isDifficultyBalanced(array $counts, int $total): bool
    {
        if ($total < 5) {
            return true; // Too few questions to judge
        }
        
        $expectedPerDifficulty = $total / 3;
        $tolerance = 0.3; // 30% tolerance
        
        foreach ($counts as $count) {
            if (abs($count - $expectedPerDifficulty) > $expectedPerDifficulty * $tolerance) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get recommendations for difficulty balancing.
     *
     * @param array $counts
     * @param int $total
     * @return array
     */
    private function getDifficultyRecommendations(array $counts, int $total): array
    {
        $recommendations = [];
        
        if ($total < 10) {
            $recommendations[] = 'Add more questions to better balance difficulty levels';
            return $recommendations;
        }
        
        $expectedPerDifficulty = $total / 3;
        
        if ($counts['easy'] < $expectedPerDifficulty * 0.7) {
            $recommendations[] = 'Consider adding more easy questions';
        }
        
        if ($counts['medium'] < $expectedPerDifficulty * 0.7) {
            $recommendations[] = 'Consider adding more medium questions';
        }
        
        if ($counts['hard'] < $expectedPerDifficulty * 0.7) {
            $recommendations[] = 'Consider adding more hard questions';
        }
        
        if ($counts['easy'] > $expectedPerDifficulty * 1.3) {
            $recommendations[] = 'Quiz might be too easy - consider adding more medium/hard questions';
        }
        
        if ($counts['hard'] > $expectedPerDifficulty * 1.3) {
            $recommendations[] = 'Quiz might be too difficult - consider adding more easy/medium questions';
        }
        
        return $recommendations;
    }
}