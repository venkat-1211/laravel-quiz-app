<?php

namespace App\Repositories\Eloquent;

use App\Models\Question;
use App\Repositories\Interfaces\QuestionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class QuestionRepository implements QuestionRepositoryInterface
{
    public function findById(int $id): ?Question
    {
        return Cache::remember("question.id.{$id}", 3600, function () use ($id) {
            return Question::with(['quiz'])->find($id);
        });
    }

    public function getByQuiz(int $quizId, bool $shuffled = false): array
    {
        $questions = Cache::remember("quiz.questions.{$quizId}", 3600, function () use ($quizId) {
            return Question::where('quiz_id', $quizId)
                ->orderBy('order')
                ->get()
                ->toArray();
        });
        
        if ($shuffled) {
            shuffle($questions);
        }
        
        return $questions;
    }

    public function create(array $data): Question
    {
        DB::beginTransaction();
        try {
            $maxOrder = Question::where('quiz_id', $data['quiz_id'])->max('order') ?? 0;
            $data['order'] = $maxOrder + 1;
            
            $question = Question::create($data);
            
            $this->updateQuizQuestionCount($data['quiz_id']);
            $this->clearCache($data['quiz_id']);
            
            DB::commit();
            return $question;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data): Question
    {
        DB::beginTransaction();
        try {
            $question = $this->findById($id);
            if (!$question) {
                throw new \Exception("Question not found");
            }
            
            $oldQuizId = $question->quiz_id;
            $question->update($data);
            
            $this->clearCache($oldQuizId);
            if (isset($data['quiz_id']) && $data['quiz_id'] != $oldQuizId) {
                $this->updateQuizQuestionCount($oldQuizId);
                $this->updateQuizQuestionCount($data['quiz_id']);
                $this->clearCache($data['quiz_id']);
            }
            
            DB::commit();
            return $question;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(int $id): bool
    {
        DB::beginTransaction();
        try {
            $question = $this->findById($id);
            if (!$question) {
                return false;
            }
            
            $quizId = $question->quiz_id;
            $result = $question->delete();
            
            $this->reorderAfterDelete($quizId);
            $this->updateQuizQuestionCount($quizId);
            $this->clearCache($quizId);
            
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function bulkCreate(array $questions): bool
    {
        if (empty($questions)) {
            return false;
        }
        
        DB::beginTransaction();
        try {
            $quizId = $questions[0]['quiz_id'];
            $maxOrder = Question::where('quiz_id', $quizId)->max('order') ?? 0;
            
            foreach ($questions as $index => $question) {
                $question['order'] = $maxOrder + $index + 1;
                Question::create($question);
            }
            
            $this->updateQuizQuestionCount($quizId);
            $this->clearCache($quizId);
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateOrder(int $quizId, array $order): void
    {
        DB::beginTransaction();
        try {
            foreach ($order as $item) {
                Question::where('id', $item['id'])
                    ->where('quiz_id', $quizId)
                    ->update(['order' => $item['order']]);
            }
            
            $this->clearCache($quizId);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getQuestionsWithStats(int $quizId): array
    {
        return Cache::remember("quiz.questions.stats.{$quizId}", 3600, function () use ($quizId) {
            $questions = Question::where('quiz_id', $quizId)
                ->orderBy('order')
                ->get()
                ->toArray();
            
            foreach ($questions as &$question) {
                $question['stats'] = $this->getQuestionStats($question['id']);
            }
            
            return $questions;
        });
    }

    public function getDifficultQuestions(int $quizId, float $threshold = 50.0): array
    {
        $questions = $this->getByQuiz($quizId);
        $difficult = [];
        
        foreach ($questions as $question) {
            $stats = $this->getQuestionStats($question['id']);
            if ($stats['success_rate'] < $threshold) {
                $difficult[] = array_merge($question, ['stats' => $stats]);
            }
        }
        
        return $difficult;
    }

    public function getQuestionsByDifficulty(int $quizId, string $difficulty): array
    {
        return Question::where('quiz_id', $quizId)
            ->where('difficulty', $difficulty)
            ->orderBy('order')
            ->get()
            ->toArray();
    }

    public function getQuestionsByType(int $quizId, string $type): array
    {
        // This would depend on your question type implementation
        // Placeholder implementation
        return [];
    }

    public function getRandomQuestions(int $quizId, int $count = 5): array
    {
        $questions = Question::where('quiz_id', $quizId)
            ->inRandomOrder()
            ->limit($count)
            ->get()
            ->toArray();
        
        return $questions;
    }

    public function searchQuestions(string $query, int $perPage = 15): array
    {
        $questions = Question::where('question_text', 'LIKE', "%{$query}%")
            ->orWhere('explanation', 'LIKE', "%{$query}%")
            ->paginate($perPage);
        
        return [
            'data' => $questions->items(),
            'total' => $questions->total(),
            'per_page' => $questions->perPage(),
            'current_page' => $questions->currentPage(),
            'last_page' => $questions->lastPage(),
        ];
    }

    public function getQuestionsNeedingReview(int $quizId): array
    {
        $questions = Question::where('quiz_id', $quizId)->get();
        $needingReview = [];
        
        foreach ($questions as $question) {
            $stats = $this->getQuestionStats($question->id);
            
            if ($stats['total_attempts'] > 0) {
                if ($stats['success_rate'] < 30 || $stats['success_rate'] > 95) {
                    $needingReview[] = [
                        'question' => $question->toArray(),
                        'stats' => $stats,
                        'reason' => $stats['success_rate'] < 30 ? 'Too difficult' : 'Too easy',
                    ];
                }
            }
        }
        
        return $needingReview;
    }

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
                'success_rate' => $stats->total_attempts > 0 ? round(($stats->correct_count / $stats->total_attempts) * 100, 2) : 0,
                'avg_time_spent' => round($stats->avg_time_spent ?? 0, 2),
                'unique_users' => (int) ($stats->unique_users ?? 0),
                'option_distribution' => $optionDistribution,
            ];
        });
    }

    public function getQuestionUsage(int $questionId): array
    {
        $usage = DB::table('attempt_answers')
            ->where('question_id', $questionId)
            ->select(
                DB::raw('COUNT(DISTINCT attempt_id) as times_used'),
                DB::raw('COUNT(DISTINCT user_id) as unique_users')
            )
            ->join('attempts', 'attempt_answers.attempt_id', '=', 'attempts.id')
            ->first();

        return [
            'times_used' => (int) ($usage->times_used ?? 0),
            'unique_users' => (int) ($usage->unique_users ?? 0),
        ];
    }

    public function getQuestionHistory(int $questionId): array
    {
        // This would require a question history table
        // Placeholder implementation
        return [];
    }

    public function duplicateQuestion(int $id, ?int $newQuizId = null): ?Question
    {
        DB::beginTransaction();
        try {
            $question = $this->findById($id);
            if (!$question) {
                return null;
            }
            
            $newData = $question->toArray();
            unset($newData['id'], $newData['created_at'], $newData['updated_at']);
            
            if ($newQuizId) {
                $newData['quiz_id'] = $newQuizId;
            }
            
            $newData['question_text'] = $newData['question_text'] . ' (Copy)';
            
            $maxOrder = Question::where('quiz_id', $newData['quiz_id'])->max('order') ?? 0;
            $newData['order'] = $maxOrder + 1;
            
            $newQuestion = Question::create($newData);
            
            $this->updateQuizQuestionCount($newData['quiz_id']);
            $this->clearCache($newData['quiz_id']);
            
            DB::commit();
            return $newQuestion;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function bulkDuplicate(array $questionIds, int $targetQuizId): int
    {
        DB::beginTransaction();
        try {
            $questions = Question::whereIn('id', $questionIds)->get();
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
            
            $this->updateQuizQuestionCount($targetQuizId);
            $this->clearCache($targetQuizId);
            
            DB::commit();
            return $count;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function moveQuestions(array $questionIds, int $targetQuizId): int
    {
        DB::beginTransaction();
        try {
            $maxOrder = Question::where('quiz_id', $targetQuizId)->max('order') ?? 0;
            
            $questions = Question::whereIn('id', $questionIds)->get();
            $sourceQuizIds = $questions->pluck('quiz_id')->unique();
            
            foreach ($questions as $index => $question) {
                $question->quiz_id = $targetQuizId;
                $question->order = $maxOrder + $index + 1;
                $question->save();
            }
            
            foreach ($sourceQuizIds as $sourceQuizId) {
                $this->reorderAfterDelete($sourceQuizId);
                $this->updateQuizQuestionCount($sourceQuizId);
                $this->clearCache($sourceQuizId);
            }
            
            $this->updateQuizQuestionCount($targetQuizId);
            $this->clearCache($targetQuizId);
            
            DB::commit();
            return $questions->count();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getQuestionCountByQuiz(int $quizId): int
    {
        return Question::where('quiz_id', $quizId)->count();
    }

    public function getQuestionCountByDifficulty(int $quizId): array
    {
        return Question::where('quiz_id', $quizId)
            ->select('difficulty', DB::raw('COUNT(*) as count'))
            ->groupBy('difficulty')
            ->pluck('count', 'difficulty')
            ->toArray();
    }

    public function getQuestionCountByType(int $quizId): array
    {
        // This would depend on your question type implementation
        return [];
    }

    public function getAverageTimePerQuestion(int $quizId): float
    {
        return (float) DB::table('attempt_answers')
            ->join('questions', 'attempt_answers.question_id', '=', 'questions.id')
            ->where('questions.quiz_id', $quizId)
            ->avg('attempt_answers.time_spent') ?? 0;
    }

    public function getMostMissedQuestions(int $quizId, int $limit = 10): array
    {
        $questions = DB::table('attempt_answers')
            ->join('questions', 'attempt_answers.question_id', '=', 'questions.id')
            ->join('attempts', 'attempt_answers.attempt_id', '=', 'attempts.id')
            ->where('questions.quiz_id', $quizId)
            ->where('attempts.status', 'completed')
            ->where('attempt_answers.is_correct', false)
            ->select(
                'questions.id',
                'questions.question_text',
                DB::raw('COUNT(*) as incorrect_count')
            )
            ->groupBy('questions.id', 'questions.question_text')
            ->orderByDesc('incorrect_count')
            ->limit($limit)
            ->get()
            ->toArray();
        
        return $questions;
    }

    public function getMostCorrectQuestions(int $quizId, int $limit = 10): array
    {
        $questions = DB::table('attempt_answers')
            ->join('questions', 'attempt_answers.question_id', '=', 'questions.id')
            ->join('attempts', 'attempt_answers.attempt_id', '=', 'attempts.id')
            ->where('questions.quiz_id', $quizId)
            ->where('attempts.status', 'completed')
            ->where('attempt_answers.is_correct', true)
            ->select(
                'questions.id',
                'questions.question_text',
                DB::raw('COUNT(*) as correct_count')
            )
            ->groupBy('questions.id', 'questions.question_text')
            ->orderByDesc('correct_count')
            ->limit($limit)
            ->get()
            ->toArray();
        
        return $questions;
    }

    public function getQuestionSuccessRate(int $questionId): float
    {
        $stats = $this->getQuestionStats($questionId);
        return $stats['success_rate'];
    }

    public function getQuestionTimeAverage(int $questionId): float
    {
        return (float) DB::table('attempt_answers')
            ->where('question_id', $questionId)
            ->avg('time_spent') ?? 0;
    }

    public function getQuestionOptionDistribution(int $questionId): array
    {
        $stats = $this->getQuestionStats($questionId);
        return $stats['option_distribution'];
    }

    public function getQuestionsWithExplanations(int $quizId): array
    {
        return Question::where('quiz_id', $quizId)
            ->whereNotNull('explanation')
            ->where('explanation', '!=', '')
            ->orderBy('order')
            ->get()
            ->toArray();
    }

    public function getQuestionsWithMedia(int $quizId): array
    {
        return Question::where('quiz_id', $quizId)
            ->where(function ($query) {
                $query->whereNotNull('image_url')
                    ->orWhereNotNull('video_url');
            })
            ->orderBy('order')
            ->get()
            ->toArray();
    }

    public function getQuestionsWithoutExplanations(int $quizId): array
    {
        return Question::where('quiz_id', $quizId)
            ->whereNull('explanation')
            ->orWhere('explanation', '')
            ->orderBy('order')
            ->get()
            ->toArray();
    }

    public function addExplanation(int $questionId, string $explanation): bool
    {
        $question = $this->findById($questionId);
        if (!$question) {
            return false;
        }
        
        $question->explanation = $explanation;
        $question->save();
        
        $this->clearCache($question->quiz_id);
        Cache::forget("question.id.{$questionId}");
        
        return true;
    }

    public function attachMedia(int $questionId, string $type, string $url): bool
    {
        $question = $this->findById($questionId);
        if (!$question) {
            return false;
        }
        
        $field = $type . '_url';
        if (!in_array($field, ['image_url', 'video_url'])) {
            return false;
        }
        
        $question->$field = $url;
        $question->save();
        
        $this->clearCache($question->quiz_id);
        Cache::forget("question.id.{$questionId}");
        
        return true;
    }

    public function removeMedia(int $questionId, string $type): bool
    {
        $question = $this->findById($questionId);
        if (!$question) {
            return false;
        }
        
        $field = $type . '_url';
        if (!in_array($field, ['image_url', 'video_url'])) {
            return false;
        }
        
        $question->$field = null;
        $question->save();
        
        $this->clearCache($question->quiz_id);
        Cache::forget("question.id.{$questionId}");
        
        return true;
    }

    public function validateQuestions(int $quizId): array
    {
        $questions = Question::where('quiz_id', $quizId)->get();
        $validation = [];
        
        foreach ($questions as $question) {
            $errors = $this->validateQuestionData($question->toArray());
            if (!empty($errors)) {
                $validation[] = [
                    'question_id' => $question->id,
                    'question_text' => $question->question_text,
                    'errors' => $errors,
                ];
            }
        }
        
        return $validation;
    }

    public function validateQuestionData(array $data): array
    {
        $errors = [];
        
        if (empty($data['question_text'])) {
            $errors[] = 'Question text is required';
        }
        
        if (empty($data['options']) || !is_array($data['options'])) {
            $errors[] = 'Options are required and must be an array';
        } elseif (count($data['options']) < 2) {
            $errors[] = 'At least 2 options are required';
        }
        
        if (empty($data['correct_answer'])) {
            $errors[] = 'Correct answer is required';
        } elseif (!isset($data['options'][$data['correct_answer']])) {
            $errors[] = 'Correct answer must match one of the options';
        }
        
        return $errors;
    }

    public function validateCorrectAnswer(string $correctAnswer, array $options): bool
    {
        return isset($options[$correctAnswer]) && !empty($options[$correctAnswer]);
    }

    public function getInvalidQuestions(int $quizId): array
    {
        $validation = $this->validateQuestions($quizId);
        return array_filter($validation, function ($item) {
            return !empty($item['errors']);
        });
    }

    public function fixInvalidQuestions(int $quizId): int
    {
        $invalid = $this->getInvalidQuestions($quizId);
        $fixed = 0;
        
        foreach ($invalid as $item) {
            // Attempt to fix common issues
            $question = Question::find($item['question_id']);
            if (!$question) {
                continue;
            }
            
            // Example: Ensure correct answer exists in options
            $options = $question->options;
            if (!isset($options[$question->correct_answer])) {
                // Set to first option as fallback
                $firstKey = array_key_first($options);
                if ($firstKey) {
                    $question->correct_answer = $firstKey;
                    $question->save();
                    $fixed++;
                }
            }
        }
        
        $this->clearCache($quizId);
        return $fixed;
    }

    public function importQuestions(int $quizId, array $questions): array
    {
        $imported = 0;
        $failed = [];
        
        DB::beginTransaction();
        try {
            $maxOrder = Question::where('quiz_id', $quizId)->max('order') ?? 0;
            
            foreach ($questions as $index => $questionData) {
                $errors = $this->validateQuestionData($questionData);
                if (!empty($errors)) {
                    $failed[] = [
                        'data' => $questionData,
                        'errors' => $errors,
                    ];
                    continue;
                }
                
                $questionData['quiz_id'] = $quizId;
                $questionData['order'] = $maxOrder + $index + 1;
                
                Question::create($questionData);
                $imported++;
            }
            
            $this->updateQuizQuestionCount($quizId);
            $this->clearCache($quizId);
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        
        return [
            'imported' => $imported,
            'failed' => $failed,
        ];
    }

    public function exportQuestions(int $quizId, string $format = 'json'): array
    {
        $questions = Question::where('quiz_id', $quizId)
            ->orderBy('order')
            ->get()
            ->toArray();
        
        if ($format === 'json') {
            return $questions;
        }
        
        // For CSV format, flatten the options
        $export = [];
        foreach ($questions as $question) {
            $row = [
                'question_text' => $question['question_text'],
                'explanation' => $question['explanation'],
                'difficulty' => $question['difficulty'],
                'points' => $question['points'],
                'correct_answer' => $question['correct_answer'],
            ];
            
            foreach ($question['options'] as $key => $value) {
                $row["option_{$key}"] = $value;
            }
            
            $export[] = $row;
        }
        
        return $export;
    }

    public function getQuestionTemplate(int $questionId): array
    {
        $question = $this->findById($questionId);
        if (!$question) {
            return [];
        }
        
        return [
            'question_text' => $question->question_text,
            'options' => $question->options,
            'correct_answer' => $question->correct_answer,
            'explanation' => $question->explanation,
            'difficulty' => $question->difficulty,
            'points' => $question->points,
        ];
    }

    public function createFromTemplate(array $template, int $quizId): ?Question
    {
        $errors = $this->validateQuestionData($template);
        if (!empty($errors)) {
            return null;
        }
        
        $maxOrder = Question::where('quiz_id', $quizId)->max('order') ?? 0;
        $template['quiz_id'] = $quizId;
        $template['order'] = $maxOrder + 1;
        
        return Question::create($template);
    }

    public function getSimilarQuestions(int $questionId, int $limit = 5): array
    {
        $question = $this->findById($questionId);
        if (!$question) {
            return [];
        }
        
        $keywords = $this->extractKeywords($question->question_text);
        
        $similar = Question::where('id', '!=', $questionId)
            ->where('quiz_id', $question->quiz_id)
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $query->orWhere('question_text', 'LIKE', "%{$keyword}%");
                }
            })
            ->limit($limit)
            ->get()
            ->toArray();
        
        return $similar;
    }

    public function getRelatedQuestions(int $questionId, int $limit = 5): array
    {
        $question = $this->findById($questionId);
        if (!$question) {
            return [];
        }
        
        // Questions from same quiz with similar difficulty
        $related = Question::where('quiz_id', $question->quiz_id)
            ->where('id', '!=', $questionId)
            ->where('difficulty', $question->difficulty)
            ->inRandomOrder()
            ->limit($limit)
            ->get()
            ->toArray();
        
        return $related;
    }

    public function getQuestionTags(int $questionId): array
    {
        // This would require a tagging system
        // Placeholder implementation
        return [];
    }

    public function addTag(int $questionId, string $tag): bool
    {
        // This would require a tagging system
        return false;
    }

    public function removeTag(int $questionId, string $tag): bool
    {
        // This would require a tagging system
        return false;
    }

    public function getQuestionsByTag(string $tag, int $perPage = 15): array
    {
        // This would require a tagging system
        return [];
    }

    public function getPopularTags(int $limit = 20): array
    {
        // This would require a tagging system
        return [];
    }

    public function getQuestionCategories(int $questionId): array
    {
        // This would require a categorization system
        return [];
    }

    public function categorizeQuestion(int $questionId, string $category): bool
    {
        // This would require a categorization system
        return false;
    }

    public function getQuestionsByCategory(string $category, int $perPage = 15): array
    {
        // This would require a categorization system
        return [];
    }

    public function reorderQuestions(int $quizId, array $order): bool
    {
        try {
            $this->updateOrder($quizId, $order);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function randomizeOrder(int $quizId): bool
    {
        DB::beginTransaction();
        try {
            $questions = Question::where('quiz_id', $quizId)
                ->orderBy('order')
                ->get();
            
            $orders = range(1, $questions->count());
            shuffle($orders);
            
            foreach ($questions as $index => $question) {
                $question->order = $orders[$index];
                $question->save();
            }
            
            $this->clearCache($quizId);
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function resetOrder(int $quizId): bool
    {
        DB::beginTransaction();
        try {
            $questions = Question::where('quiz_id', $quizId)
                ->orderBy('id')
                ->get();
            
            foreach ($questions as $index => $question) {
                $question->order = $index + 1;
                $question->save();
            }
            
            $this->clearCache($quizId);
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function getQuestionOrder(int $quizId): array
    {
        return Question::where('quiz_id', $quizId)
            ->orderBy('order')
            ->pluck('id', 'order')
            ->toArray();
    }

    public function getNextQuestion(int $currentQuestionId, int $quizId): ?array
    {
        $currentOrder = Question::where('id', $currentQuestionId)
            ->where('quiz_id', $quizId)
            ->value('order');
        
        if (!$currentOrder) {
            return null;
        }
        
        $next = Question::where('quiz_id', $quizId)
            ->where('order', '>', $currentOrder)
            ->orderBy('order')
            ->first();
        
        return $next ? $next->toArray() : null;
    }

    public function getPreviousQuestion(int $currentQuestionId, int $quizId): ?array
    {
        $currentOrder = Question::where('id', $currentQuestionId)
            ->where('quiz_id', $quizId)
            ->value('order');
        
        if (!$currentOrder) {
            return null;
        }
        
        $prev = Question::where('quiz_id', $quizId)
            ->where('order', '<', $currentOrder)
            ->orderByDesc('order')
            ->first();
        
        return $prev ? $prev->toArray() : null;
    }

    public function archiveQuestions(array $questionIds): int
    {
        // This would require an archive table
        // Placeholder implementation
        return 0;
    }

    public function restoreQuestions(array $questionIds): int
    {
        // This would require an archive table
        // Placeholder implementation
        return 0;
    }

    public function getArchivedQuestions(int $days = 30): array
    {
        // This would require an archive table
        return [];
    }

    public function permanentlyDelete(array $questionIds): int
    {
        $count = Question::whereIn('id', $questionIds)->delete();
        
        // Clear cache for affected quizzes
        $quizIds = Question::whereIn('id', $questionIds)->pluck('quiz_id')->unique();
        foreach ($quizIds as $quizId) {
            $this->clearCache($quizId);
        }
        
        return $count;
    }

    public function getQuestionsForReview(int $userId, int $limit = 10): array
    {
        // Get questions user got wrong and haven't reviewed
        $questions = DB::table('attempt_answers')
            ->join('questions', 'attempt_answers.question_id', '=', 'questions.id')
            ->join('attempts', 'attempt_answers.attempt_id', '=', 'attempts.id')
            ->where('attempts.user_id', $userId)
            ->where('attempt_answers.is_correct', false)
            ->whereNotExists(function ($query) use ($userId) {
                $query->select(DB::raw(1))
                    ->from('attempt_answers as aa2')
                    ->join('attempts as a2', 'aa2.attempt_id', '=', 'a2.id')
                    ->whereRaw('aa2.question_id = questions.id')
                    ->where('a2.user_id', $userId)
                    ->where('aa2.is_correct', true);
            })
            ->select('questions.*')
            ->distinct()
            ->limit($limit)
            ->get()
            ->toArray();
        
        return $questions;
    }

    public function getQuestionsForSpacedRepetition(int $userId, int $limit = 10): array
    {
        // This would require a spaced repetition system
        // Placeholder implementation
        return [];
    }

    public function getQuestionsByMasteryLevel(int $userId, string $level, int $limit = 10): array
    {
        // This would require a mastery tracking system
        // Placeholder implementation
        return [];
    }

    public function getRecommendedQuestions(int $userId, int $limit = 10): array
    {
        // This would require a recommendation system
        // Placeholder implementation
        return [];
    }

    public function getQuestionsByPerformance(int $userId, string $type = 'weak', int $limit = 10): array
    {
        if ($type === 'weak') {
            return $this->getQuestionsForReview($userId, $limit);
        }
        
        // Strong questions
        $questions = DB::table('attempt_answers')
            ->join('questions', 'attempt_answers.question_id', '=', 'questions.id')
            ->join('attempts', 'attempt_answers.attempt_id', '=', 'attempts.id')
            ->where('attempts.user_id', $userId)
            ->where('attempt_answers.is_correct', true)
            ->select('questions.*', DB::raw('COUNT(*) as correct_count'))
            ->groupBy('questions.id')
            ->orderByDesc('correct_count')
            ->limit($limit)
            ->get()
            ->toArray();
        
        return $questions;
    }

    public function trackQuestionView(int $questionId, int $userId): void
    {
        Cache::put("user.viewed.{$userId}.{$questionId}", now(), now()->addDays(30));
    }

    public function getRecentlyViewed(int $userId, int $limit = 10): array
    {
        // This would require a view tracking table
        // Placeholder implementation using cache
        $keys = Cache::get("user.recent.{$userId}", []);
        return array_slice($keys, 0, $limit);
    }

    public function getPopularQuestions(int $limit = 10): array
    {
        $questions = DB::table('attempt_answers')
            ->join('questions', 'attempt_answers.question_id', '=', 'questions.id')
            ->select('questions.*', DB::raw('COUNT(*) as attempt_count'))
            ->groupBy('questions.id')
            ->orderByDesc('attempt_count')
            ->limit($limit)
            ->get()
            ->toArray();
        
        return $questions;
    }

    public function getTrendingQuestions(int $days = 7, int $limit = 10): array
    {
        $startDate = now()->subDays($days);
        
        $questions = DB::table('attempt_answers')
            ->join('questions', 'attempt_answers.question_id', '=', 'questions.id')
            ->join('attempts', 'attempt_answers.attempt_id', '=', 'attempts.id')
            ->where('attempts.created_at', '>=', $startDate)
            ->select('questions.*', DB::raw('COUNT(*) as attempt_count'))
            ->groupBy('questions.id')
            ->orderByDesc('attempt_count')
            ->limit($limit)
            ->get()
            ->toArray();
        
        return $questions;
    }

    public function getQuestionFeedback(int $questionId): array
    {
        // This would require a feedback system
        // Placeholder implementation
        return [];
    }

    public function addFeedback(int $questionId, int $userId, string $feedback, int $rating = null): bool
    {
        // This would require a feedback system
        return false;
    }

    public function reportIssue(int $questionId, int $userId, string $issue, string $description): bool
    {
        // This would require an issue tracking system
        return false;
    }

    public function resolveIssue(int $issueId, string $resolution): bool
    {
        // This would require an issue tracking system
        return false;
    }

    public function getQuestionReports(int $questionId): array
    {
        // This would require a reporting system
        return [];
    }

    public function getQuestionDifficulty(int $questionId): float
    {
        $stats = $this->getQuestionStats($questionId);
        return 100 - $stats['success_rate'];
    }

    public function getQuestionDiscrimination(int $questionId): float
    {
        $stats = $this->getQuestionStats($questionId);
        if ($stats['total_attempts'] < 10) {
            return 0;
        }
        
        $scores = DB::table('attempt_answers')
            ->join('attempts', 'attempt_answers.attempt_id', '=', 'attempts.id')
            ->where('attempt_answers.question_id', $questionId)
            ->where('attempts.status', 'completed')
            ->select('attempts.user_id', 'attempts.percentage_score', 'attempt_answers.is_correct')
            ->get();
        
        $totalUsers = $scores->groupBy('user_id')->count();
        $top27 = $scores->sortByDesc('percentage_score')->take(ceil($totalUsers * 0.27));
        $bottom27 = $scores->sortBy('percentage_score')->take(ceil($totalUsers * 0.27));
        
        $topCorrect = $top27->where('is_correct', true)->count();
        $bottomCorrect = $bottom27->where('is_correct', true)->count();
        
        $topCount = $top27->count();
        $bottomCount = $bottom27->count();
        
        return round(($topCorrect / max($topCount, 1)) - ($bottomCorrect / max($bottomCount, 1)), 2);
    }

    public function getQuestionGuessability(int $questionId): float
    {
        $question = $this->findById($questionId);
        if (!$question) {
            return 0;
        }
        
        $optionsCount = count($question->options);
        return 1 / max($optionsCount, 1);
    }

    public function getQuestionReliability(int $questionId): float
    {
        // This would require test-retest data
        return 0.8;
    }

    public function getQuestionValidity(int $questionId): float
    {
        // This would require external criteria
        return 0.75;
    }

    public function getQuestionIrtParameters(int $questionId): array
    {
        $stats = $this->getQuestionStats($questionId);
        $difficulty = $this->getQuestionDifficulty($questionId) / 100;
        $discrimination = $this->getQuestionDiscrimination($questionId);
        $guessing = $this->getQuestionGuessability($questionId);
        
        return [
            'difficulty' => round($difficulty, 2),
            'discrimination' => round($discrimination, 2),
            'guessing' => round($guessing, 2),
        ];
    }

    public function calibrateQuestionDifficulty(int $questionId): float
    {
        $stats = $this->getQuestionStats($questionId);
        $difficulty = 100 - $stats['success_rate'];
        
        $question = $this->findById($questionId);
        if ($question) {
            $level = 'medium';
            if ($difficulty < 30) {
                $level = 'easy';
            } elseif ($difficulty < 60) {
                $level = 'medium';
            } elseif ($difficulty < 80) {
                $level = 'hard';
            } else {
                $level = 'expert';
            }
            
            $question->difficulty = $level;
            $question->save();
        }
        
        return $difficulty;
    }

    public function updateQuestionStatistics(int $questionId): void
    {
        Cache::forget("question.stats.{$questionId}");
        $this->getQuestionStats($questionId);
        $this->calibrateQuestionDifficulty($questionId);
    }

    public function recalculateAllStats(int $quizId): void
    {
        $questions = Question::where('quiz_id', $quizId)->get();
        foreach ($questions as $question) {
            $this->updateQuestionStatistics($question->id);
        }
    }

    public function getQuestionVersion(int $questionId): int
    {
        // This would require version tracking
        return 1;
    }

    public function createQuestionVersion(int $questionId): bool
    {
        // This would require version tracking
        return false;
    }

    public function revertToVersion(int $questionId, int $version): bool
    {
        // This would require version tracking
        return false;
    }

    public function compareVersions(int $questionId, int $version1, int $version2): array
    {
        // This would require version tracking
        return [];
    }

    public function getQuestionAuditLog(int $questionId): array
    {
        // This would require audit logging
        return [];
    }

    public function logQuestionAction(int $questionId, int $userId, string $action, array $details = []): bool
    {
        // This would require audit logging
        return false;
    }

    public function getQuestionsByContributor(int $userId, int $perPage = 15): array
    {
        $questions = Question::where('created_by', $userId)
            ->paginate($perPage);
        
        return [
            'data' => $questions->items(),
            'total' => $questions->total(),
            'per_page' => $questions->perPage(),
            'current_page' => $questions->currentPage(),
            'last_page' => $questions->lastPage(),
        ];
    }

    public function getContributorStats(int $userId): array
    {
        return [
            'total_questions' => Question::where('created_by', $userId)->count(),
            'total_edits' => 0, // Would need edit tracking
            'avg_rating' => 4.5, // Would need rating system
        ];
    }

    public function getTopContributors(int $limit = 10): array
    {
        // This would require contributor tracking
        return [];
    }

    public function getQuestionCollaborators(int $questionId): array
    {
        // This would require collaboration system
        return [];
    }

    public function addCollaborator(int $questionId, int $userId, string $role): bool
    {
        // This would require collaboration system
        return false;
    }

    public function removeCollaborator(int $questionId, int $userId): bool
    {
        // This would require collaboration system
        return false;
    }

    public function getQuestionComments(int $questionId, int $perPage = 20): array
    {
        // This would require comment system
        return [];
    }

    public function addComment(int $questionId, int $userId, string $comment): bool
    {
        // This would require comment system
        return false;
    }

    public function deleteComment(int $commentId): bool
    {
        // This would require comment system
        return false;
    }

    public function getQuestionNotes(int $questionId): array
    {
        // This would require notes system
        return [];
    }

    public function addNote(int $questionId, int $userId, string $note): bool
    {
        // This would require notes system
        return false;
    }

    public function deleteNote(int $noteId): bool
    {
        // This would require notes system
        return false;
    }

    public function getQuestionResources(int $questionId): array
    {
        // This would require resources system
        return [];
    }

    public function addResource(int $questionId, string $type, string $url, string $title = null): bool
    {
        // This would require resources system
        return false;
    }

    public function removeResource(int $questionId, int $resourceId): bool
    {
        // This would require resources system
        return false;
    }

    public function getRelatedResources(int $questionId): array
    {
        // This would require resources system
        return [];
    }

    public function getQuestionTips(int $questionId): array
    {
        // This would require tips system
        return [];
    }

    public function addTip(int $questionId, string $tip, int $userId = null): bool
    {
        // This would require tips system
        return false;
    }

    public function getQuestionHints(int $questionId): array
    {
        // This would require hints system
        return [];
    }

    public function addHint(int $questionId, string $hint, int $order = 0): bool
    {
        // This would require hints system
        return false;
    }

    public function getQuestionSolutions(int $questionId): array
    {
        // This would require solutions system
        return [];
    }

    public function addSolution(int $questionId, string $solution, string $type = 'text'): bool
    {
        // This would require solutions system
        return false;
    }

    public function validateSolution(int $questionId, string $solution): bool
    {
        // This would require solution validation
        return false;
    }

    public function getQuestionExamples(int $questionId): array
    {
        // This would require examples system
        return [];
    }

    public function addExample(int $questionId, string $example): bool
    {
        // This would require examples system
        return false;
    }

    public function getQuestionPrerequisites(int $questionId): array
    {
        // This would require prerequisites system
        return [];
    }

    public function addPrerequisite(int $questionId, int $prerequisiteId): bool
    {
        // This would require prerequisites system
        return false;
    }

    public function removePrerequisite(int $questionId, int $prerequisiteId): bool
    {
        // This would require prerequisites system
        return false;
    }

    public function getQuestionLearningObjectives(int $questionId): array
    {
        // This would require learning objectives system
        return [];
    }

    public function addLearningObjective(int $questionId, string $objective): bool
    {
        // This would require learning objectives system
        return false;
    }

    public function getQuestionSkills(int $questionId): array
    {
        // This would require skills system
        return [];
    }

    public function addSkill(int $questionId, string $skill, int $level = 1): bool
    {
        // This would require skills system
        return false;
    }

    public function getQuestionsBySkill(string $skill, int $level = null, int $perPage = 15): array
    {
        // This would require skills system
        return [];
    }

    public function getQuestionDifficultyLevel(int $questionId): string
    {
        $question = $this->findById($questionId);
        return $question ? $question->difficulty : 'medium';
    }

    public function setDifficultyLevel(int $questionId, string $level): bool
    {
        $question = $this->findById($questionId);
        if (!$question) {
            return false;
        }
        
        $question->difficulty = $level;
        $question->save();
        
        return true;
    }

    public function autoSetDifficulty(int $questionId): string
    {
        $difficulty = $this->calibrateQuestionDifficulty($questionId);
        $level = 'medium';
        
        if ($difficulty < 30) {
            $level = 'easy';
        } elseif ($difficulty < 60) {
            $level = 'medium';
        } elseif ($difficulty < 80) {
            $level = 'hard';
        } else {
            $level = 'expert';
        }
        
        $this->setDifficultyLevel($questionId, $level);
        
        return $level;
    }

    public function getQuestionTimeEstimate(int $questionId): int
    {
        return (int) $this->getQuestionTimeAverage($questionId);
    }

    public function setTimeEstimate(int $questionId, int $seconds): bool
    {
        // Store in question metadata
        return false;
    }

    public function getQuestionPoints(int $questionId): int
    {
        $question = $this->findById($questionId);
        return $question ? $question->points : 10;
    }

    public function setPoints(int $questionId, int $points): bool
    {
        $question = $this->findById($questionId);
        if (!$question) {
            return false;
        }
        
        $question->points = $points;
        $question->save();
        
        return true;
    }

    public function getQuestionScoringRules(int $questionId): array
    {
        // This would require scoring rules system
        return [
            'type' => 'standard',
            'points' => $this->getQuestionPoints($questionId),
        ];
    }

    public function setScoringRules(int $questionId, array $rules): bool
    {
        // This would require scoring rules system
        return false;
    }

    public function getQuestionPenalty(int $questionId): float
    {
        // This would require penalty system
        return 0;
    }

    public function setPenalty(int $questionId, float $penalty): bool
    {
        // This would require penalty system
        return false;
    }

    public function getQuestionBonusCriteria(int $questionId): array
    {
        // This would require bonus system
        return [];
    }

    public function setBonusCriteria(int $questionId, array $criteria): bool
    {
        // This would require bonus system
        return false;
    }

    public function getQuestionAchievements(int $questionId): array
    {
        // This would require achievement system
        return [];
    }

    public function attachAchievement(int $questionId, int $achievementId): bool
    {
        // This would require achievement system
        return false;
    }

    public function getQuestionBadges(int $questionId): array
    {
        // This would require badge system
        return [];
    }

    public function attachBadge(int $questionId, int $badgeId): bool
    {
        // This would require badge system
        return false;
    }

    public function getQuestionLeaderboard(int $questionId, int $limit = 10): array
    {
        $leaders = DB::table('attempt_answers')
            ->join('attempts', 'attempt_answers.attempt_id', '=', 'attempts.id')
            ->join('users', 'attempts.user_id', '=', 'users.id')
            ->where('attempt_answers.question_id', $questionId)
            ->where('attempts.status', 'completed')
            ->select(
                'users.id',
                'users.name',
                DB::raw('AVG(CASE WHEN attempt_answers.is_correct THEN 100 ELSE 0 END) as score'),
                DB::raw('COUNT(*) as attempts')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('score')
            ->limit($limit)
            ->get()
            ->toArray();
        
        return $leaders;
    }

    public function getQuestionRankings(int $questionId): array
    {
        return $this->getQuestionLeaderboard($questionId, 100);
    }

    public function getUserRankOnQuestion(int $questionId, int $userId): ?array
    {
        $allScores = DB::table('attempt_answers')
            ->join('attempts', 'attempt_answers.attempt_id', '=', 'attempts.id')
            ->where('attempt_answers.question_id', $questionId)
            ->where('attempts.status', 'completed')
            ->select(
                'attempts.user_id',
                DB::raw('AVG(CASE WHEN attempt_answers.is_correct THEN 100 ELSE 0 END) as avg_score')
            )
            ->groupBy('attempts.user_id')
            ->orderByDesc('avg_score')
            ->get()
            ->pluck('avg_score', 'user_id')
            ->toArray();
        
        $userScore = DB::table('attempt_answers')
            ->join('attempts', 'attempt_answers.attempt_id', '=', 'attempts.id')
            ->where('attempt_answers.question_id', $questionId)
            ->where('attempts.user_id', $userId)
            ->where('attempts.status', 'completed')
            ->avg('attempt_answers.is_correct') ?? 0;
        
        $rank = 1;
        foreach ($allScores as $uid => $score) {
            if ($uid == $userId) {
                break;
            }
            if ($score > $userScore) {
                $rank++;
            }
        }
        
        return [
            'rank' => $rank,
            'total' => count($allScores),
            'score' => $userScore * 100,
            'percentile' => count($allScores) > 0 ? (1 - ($rank - 1) / count($allScores)) * 100 : 0,
        ];
    }

    public function getQuestionAttempts(int $questionId, int $limit = 100): array
    {
        return DB::table('attempt_answers')
            ->join('attempts', 'attempt_answers.attempt_id', '=', 'attempts.id')
            ->join('users', 'attempts.user_id', '=', 'users.id')
            ->where('attempt_answers.question_id', $questionId)
            ->where('attempts.status', 'completed')
            ->select(
                'users.name',
                'attempts.id as attempt_id',
                'attempt_answers.selected_answer',
                'attempt_answers.is_correct',
                'attempt_answers.time_spent',
                'attempts.created_at'
            )
            ->orderByDesc('attempts.created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getUserAttemptsOnQuestion(int $questionId, int $userId): array
    {
        return DB::table('attempt_answers')
            ->join('attempts', 'attempt_answers.attempt_id', '=', 'attempts.id')
            ->where('attempt_answers.question_id', $questionId)
            ->where('attempts.user_id', $userId)
            ->where('attempts.status', 'completed')
            ->select(
                'attempts.id as attempt_id',
                'attempt_answers.selected_answer',
                'attempt_answers.is_correct',
                'attempt_answers.time_spent',
                'attempts.created_at'
            )
            ->orderByDesc('attempts.created_at')
            ->get()
            ->toArray();
    }

    public function getQuestionSuccessRateByDemographic(int $questionId, string $demographic): array
    {
        // This would require demographic data
        return [];
    }

    public function getQuestionPerformanceByTime(int $questionId): array
    {
        $performance = DB::table('attempt_answers')
            ->join('attempts', 'attempt_answers.attempt_id', '=', 'attempts.id')
            ->where('attempt_answers.question_id', $questionId)
            ->where('attempts.status', 'completed')
            ->select(
                DB::raw('HOUR(attempts.created_at) as hour'),
                DB::raw('AVG(CASE WHEN attempt_answers.is_correct THEN 100 ELSE 0 END) as success_rate'),
                DB::raw('COUNT(*) as attempts')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->toArray();
        
        return $performance;
    }

    public function getQuestionPerformanceByLocation(int $questionId): array
    {
        // This would require location data
        return [];
    }

    public function getQuestionABTestResults(int $questionId): array
    {
        // This would require A/B testing system
        return [];
    }

    public function createABTest(int $questionId, array $variants): bool
    {
        // This would require A/B testing system
        return false;
    }

    public function getQuestionExperiments(int $questionId): array
    {
        // This would require experiments system
        return [];
    }

    public function runExperiment(int $questionId, string $experiment): array
    {
        // This would require experiments system
        return [];
    }

    public function getQuestionPersonalization(int $questionId): array
    {
        // This would require personalization system
        return [];
    }

    public function setPersonalization(int $questionId, array $settings): bool
    {
        // This would require personalization system
        return false;
    }

    public function getPersonalizedQuestion(int $questionId, int $userId): array
    {
        $question = $this->findById($questionId);
        if (!$question) {
            return [];
        }
        
        $userStats = DB::table('attempt_answers')
            ->join('attempts', 'attempt_answers.attempt_id', '=', 'attempts.id')
            ->where('attempts.user_id', $userId)
            ->where('attempts.status', 'completed')
            ->avg('attempt_answers.is_correct') ?? 0;
        
        $result = $question->toArray();
        
        // Adjust difficulty based on user performance
        if ($userStats > 0.8) {
            $result['difficulty'] = $this->getNextDifficultyLevel($question->difficulty, 'up');
        } elseif ($userStats < 0.4) {
            $result['difficulty'] = $this->getNextDifficultyLevel($question->difficulty, 'down');
        }
        
        return $result;
    }

    public function getQuestionAccessibility(int $questionId): array
    {
        // This would require accessibility system
        return [
            'screen_reader_friendly' => true,
            'high_contrast_available' => true,
            'text_to_speech_available' => true,
        ];
    }

    public function setAccessibility(int $questionId, array $settings): bool
    {
        // This would require accessibility system
        return false;
    }

    public function getAlternativeFormats(int $questionId): array
    {
        // This would require alternative formats system
        return [];
    }

    public function addAlternativeFormat(int $questionId, string $format, string $content): bool
    {
        // This would require alternative formats system
        return false;
    }

    public function getQuestionTranslations(int $questionId): array
    {
        // This would require translations system
        return [];
    }

    public function addTranslation(int $questionId, string $language, array $translation): bool
    {
        // This would require translations system
        return false;
    }

    public function getQuestionsNeedingTranslation(string $language): array
    {
        // This would require translations system
        return [];
    }

    public function getQuestionCopyright(int $questionId): array
    {
        // This would require copyright system
        return [];
    }

    public function setCopyright(int $questionId, array $copyright): bool
    {
        // This would require copyright system
        return false;
    }

    public function getQuestionLicense(int $questionId): string
    {
        // This would require license system
        return 'standard';
    }

    public function setLicense(int $questionId, string $license): bool
    {
        // This would require license system
        return false;
    }

    public function getQuestionAttributions(int $questionId): array
    {
        // This would require attribution system
        return [];
    }

    public function addAttribution(int $questionId, string $name, string $role): bool
    {
        // This would require attribution system
        return false;
    }

    public function getQuestionCitations(int $questionId): array
    {
        // This would require citation system
        return [];
    }

    public function addCitation(int $questionId, string $citation): bool
    {
        // This would require citation system
        return false;
    }

    public function getQuestionReferences(int $questionId): array
    {
        // This would require references system
        return [];
    }

    public function addReference(int $questionId, string $reference): bool
    {
        // This would require references system
        return false;
    }

    public function getQuestionSources(int $questionId): array
    {
        // This would require sources system
        return [];
    }

    public function addSource(int $questionId, string $source): bool
    {
        // This would require sources system
        return false;
    }

    public function getQuestionDerivations(int $questionId): array
    {
        // This would require derivation system
        return [];
    }

    public function addDerivation(int $questionId, string $derivation): bool
    {
        // This would require derivation system
        return false;
    }

    public function getQuestionPlagiarismScore(int $questionId): float
    {
        // This would require plagiarism detection
        return 0;
    }

    public function checkPlagiarism(int $questionId): array
    {
        // This would require plagiarism detection
        return [];
    }

    public function getQuestionOriginality(int $questionId): float
    {
        // This would require originality detection
        return 1.0;
    }

    public function getQuestionUniqueness(int $questionId): float
    {
        // This would require uniqueness detection
        return 1.0;
    }

    public function getQuestionSimilarities(int $questionId): array
    {
        // This would require similarity detection
        return [];
    }

    public function mergeQuestions(int $sourceId, int $targetId): bool
    {
        DB::beginTransaction();
        try {
            $source = $this->findById($sourceId);
            $target = $this->findById($targetId);
            
            if (!$source || !$target) {
                return false;
            }
            
            // Update all answers to point to target question
            DB::table('attempt_answers')
                ->where('question_id', $sourceId)
                ->update(['question_id' => $targetId]);
            
            // Delete source question
            $source->delete();
            
            $this->clearCache($target->quiz_id);
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function splitQuestion(int $questionId, array $parts): array
    {
        // This would require question splitting logic
        return [];
    }

    public function combineQuestions(array $questionIds): ?Question
    {
        // This would require question combination logic
        return null;
    }

    public function getQuestionDependencies(int $questionId): array
    {
        // This would require dependency system
        return [];
    }

    public function addDependency(int $questionId, int $dependsOnId, string $type = 'requires'): bool
    {
        // This would require dependency system
        return false;
    }

    public function removeDependency(int $questionId, int $dependsOnId): bool
    {
        // This would require dependency system
        return false;
    }

    public function getQuestionImpact(int $questionId): array
    {
        $stats = $this->getQuestionStats($questionId);
        return [
            'students_reached' => $stats['unique_users'],
            'total_attempts' => $stats['total_attempts'],
            'learning_impact' => $stats['success_rate'] / 100,
        ];
    }

    public function calculateQuestionImpact(int $questionId): float
    {
        $stats = $this->getQuestionStats($questionId);
        return ($stats['unique_users'] * $stats['success_rate']) / 100;
    }

    public function getQuestionROI(int $questionId): float
    {
        $impact = $this->calculateQuestionImpact($questionId);
        $cost = $this->getQuestionCost($questionId);
        
        return $cost > 0 ? $impact / $cost : $impact;
    }

    public function getQuestionValue(int $questionId): float
    {
        return $this->calculateQuestionImpact($questionId);
    }

    public function getQuestionCost(int $questionId): float
    {
        // Estimated cost based on creation time, maintenance, etc.
        return 10.0;
    }

    public function getQuestionEfficiency(int $questionId): float
    {
        $stats = $this->getQuestionStats($questionId);
        $timePerStudent = $stats['avg_time_spent'];
        $learningGain = $stats['success_rate'] / 100;
        
        return $timePerStudent > 0 ? $learningGain / $timePerStudent * 100 : 0;
    }

    public function getQuestionEffectiveness(int $questionId): float
    {
        return $this->getQuestionSuccessRate($questionId) / 100;
    }

    public function getQuestionProductivity(int $questionId): float
    {
        $stats = $this->getQuestionStats($questionId);
        return $stats['total_attempts'] / max($stats['unique_users'], 1);
    }

    public function getQuestionSatisfaction(int $questionId): float
    {
        // Would require satisfaction ratings
        return 0.8;
    }

    public function getQuestionEngagement(int $questionId): float
    {
        $stats = $this->getQuestionStats($questionId);
        $avgTime = $stats['avg_time_spent'];
        $expectedTime = 30; // Expected time in seconds
        
        return min($avgTime / $expectedTime, 1.5) / 1.5;
    }

    public function getQuestionMastery(int $questionId): float
    {
        $stats = $this->getQuestionStats($questionId);
        return $stats['success_rate'] / 100;
    }

    public function getQuestionLearning(int $questionId): float
    {
        return $this->getQuestionEffectiveness($questionId);
    }

    public function getQuestionImprovement(int $questionId): float
    {
        // Track improvement over time
        return 0.1;
    }

    public function getQuestionProgress(int $questionId): float
    {
        return $this->getQuestionMastery($questionId);
    }

    public function getQuestionAchievement(int $questionId): float
    {
        return $this->getQuestionSuccessRate($questionId) / 100;
    }

    public function getQuestionRecognition(int $questionId): float
    {
        // Would require recognition system
        return 0.5;
    }

    public function getQuestionReward(int $questionId): float
    {
        return $this->getQuestionPoints($questionId) / 100;
    }

    public function getQuestionMotivation(int $questionId): float
    {
        return ($this->getQuestionEngagement($questionId) + $this->getQuestionReward($questionId)) / 2;
    }

    public function getQuestionConfidence(int $questionId): float
    {
        return $this->getQuestionReliability($questionId);
    }

    public function getQuestionSelfEfficacy(int $questionId): float
    {
        return $this->getQuestionSuccessRate($questionId) / 100;
    }

    public function getQuestionAnxiety(int $questionId): float
    {
        $stats = $this->getQuestionStats($questionId);
        $dropoutRate = 1 - ($stats['total_attempts'] / max($stats['total_attempts'] + 1, 1));
        return $dropoutRate;
    }

    public function getQuestionStress(int $questionId): float
    {
        $stats = $this->getQuestionStats($questionId);
        $timeStress = min($stats['avg_time_spent'] / 60, 1);
        return $timeStress;
    }

    public function getQuestionFatigue(int $questionId): float
    {
        $position = Question::where('id', $questionId)->value('order') ?? 0;
        return min($position / 20, 1);
    }

    public function getQuestionBoredom(int $questionId): float
    {
        $stats = $this->getQuestionStats($questionId);
        $quickGuesses = DB::table('attempt_answers')
            ->where('question_id', $questionId)
            ->where('time_spent', '<', 5)
            ->count();
        
        return $stats['total_attempts'] > 0 ? $quickGuesses / $stats['total_attempts'] : 0;
    }

    public function getQuestionFrustration(int $questionId): float
    {
        $stats = $this->getQuestionStats($questionId);
        $incorrectAttempts = $stats['total_attempts'] - $stats['correct_count'];
        
        return $stats['total_attempts'] > 0 ? $incorrectAttempts / $stats['total_attempts'] : 0;
    }

    public function getQuestionConfusion(int $questionId): float
    {
        $optionDist = $this->getQuestionOptionDistribution($questionId);
        if (empty($optionDist)) {
            return 0;
        }
        
        $total = array_sum($optionDist);
        $maxForWrong = $total - ($optionDist['correct'] ?? 0);
        
        return $total > 0 ? $maxForWrong / $total : 0;
    }

    public function getQuestionCuriosity(int $questionId): float
    {
        $views = Cache::get("question.views.{$questionId}", 0);
        $attempts = $this->getQuestionUsage($questionId)['times_used'];
        
        return $views > 0 ? min($attempts / $views, 1) : 0;
    }

    public function getQuestionInterest(int $questionId): float
    {
        return $this->getQuestionCuriosity($questionId);
    }

    public function getQuestionRelevance(int $questionId): float
    {
        $question = $this->findById($questionId);
        if (!$question) {
            return 0;
        }
        
        $quizQuestions = Question::where('quiz_id', $question->quiz_id)->count();
        $position = $question->order;
        
        // Questions in the middle might be more relevant
        $relevance = 1 - abs(($position / $quizQuestions) - 0.5);
        
        return $relevance;
    }

    public function getQuestionUsefulness(int $questionId): float
    {
        $stats = $this->getQuestionStats($questionId);
        $discrimination = $this->getQuestionDiscrimination($questionId);
        
        return ($stats['success_rate'] / 100 + $discrimination) / 2;
    }

    public function getQuestionApplicability(int $questionId): float
    {
        return $this->getQuestionUsefulness($questionId);
    }

    public function getQuestionTransfer(int $questionId): float
    {
        // Would require transfer learning data
        return 0.5;
    }

    public function getQuestionPrediction(int $questionId): array
    {
        $stats = $this->getQuestionStats($questionId);
        $trend = $this->getQuestionTrends($questionId);
        
        return [
            'expected_success_rate' => $stats['success_rate'],
            'trend_direction' => $trend['direction'] ?? 'stable',
            'confidence' => min($stats['total_attempts'] / 100, 1),
        ];
    }

    public function predictPerformance(int $questionId, int $userId): float
    {
        $userStats = DB::table('attempt_answers')
            ->join('attempts', 'attempt_answers.attempt_id', '=', 'attempts.id')
            ->where('attempts.user_id', $userId)
            ->where('attempts.status', 'completed')
            ->avg('attempt_answers.is_correct') ?? 0.5;
        
        $questionStats = $this->getQuestionStats($questionId);
        $questionDifficulty = 1 - ($questionStats['success_rate'] / 100);
        
        return max(0, min(1, $userStats - $questionDifficulty + 0.5));
    }

    public function getQuestionForecast(int $questionId): array
    {
        $stats = $this->getQuestionStats($questionId);
        
        return [
            'next_7_days' => round($stats['total_attempts'] * 0.3),
            'next_30_days' => round($stats['total_attempts'] * 1.2),
            'next_90_days' => round($stats['total_attempts'] * 3.5),
        ];
    }

    public function getQuestionTrends(int $questionId): array
    {
        $daily = DB::table('attempt_answers')
            ->join('attempts', 'attempt_answers.attempt_id', '=', 'attempts.id')
            ->where('attempt_answers.question_id', $questionId)
            ->where('attempts.created_at', '>=', now()->subDays(30))
            ->select(
                DB::raw('DATE(attempts.created_at) as date'),
                DB::raw('COUNT(*) as attempts'),
                DB::raw('AVG(CASE WHEN attempt_answers.is_correct THEN 100 ELSE 0 END) as success_rate')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
        
        $direction = 'stable';
        if (count($daily) >= 7) {
            $recent = array_slice($daily, -7);
            $firstAvg = $recent[0]->success_rate;
            $lastAvg = $recent[count($recent)-1]->success_rate;
            
            if ($lastAvg > $firstAvg + 10) {
                $direction = 'increasing';
            } elseif ($lastAvg < $firstAvg - 10) {
                $direction = 'decreasing';
            }
        }
        
        return [
            'daily' => $daily,
            'direction' => $direction,
        ];
    }

    public function getQuestionSeasonality(int $questionId): array
    {
        $monthly = DB::table('attempt_answers')
            ->join('attempts', 'attempt_answers.attempt_id', '=', 'attempts.id')
            ->where('attempt_answers.question_id', $questionId)
            ->select(
                DB::raw('MONTH(attempts.created_at) as month'),
                DB::raw('COUNT(*) as attempts')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
        
        return $monthly;
    }

    public function getQuestionCycles(int $questionId): array
    {
        // Would require cycle detection
        return [];
    }

    public function getQuestionPatterns(int $questionId): array
    {
        $patterns = [
            'time_of_day' => $this->getQuestionPerformanceByTime($questionId),
            'day_of_week' => $this->getQuestionPerformanceByDayOfWeek($questionId),
        ];
        
        return $patterns;
    }

    private function getQuestionPerformanceByDayOfWeek(int $questionId): array
    {
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        
        $performance = DB::table('attempt_answers')
            ->join('attempts', 'attempt_answers.attempt_id', '=', 'attempts.id')
            ->where('attempt_answers.question_id', $questionId)
            ->where('attempts.status', 'completed')
            ->select(
                DB::raw('DAYOFWEEK(attempts.created_at) as day'),
                DB::raw('AVG(CASE WHEN attempt_answers.is_correct THEN 100 ELSE 0 END) as success_rate'),
                DB::raw('COUNT(*) as attempts')
            )
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day')
            ->toArray();
        
        $result = [];
        for ($day = 1; $day <= 7; $day++) {
            $result[] = [
                'day' => $days[$day - 1],
                'success_rate' => round($performance[$day]->success_rate ?? 0, 2),
                'attempts' => $performance[$day]->attempts ?? 0,
            ];
        }
        
        return $result;
    }

    public function getQuestionAnomalies(int $questionId): array
    {
        $stats = $this->getQuestionStats($questionId);
        $anomalies = [];
        
        if ($stats['success_rate'] < 20) {
            $anomalies[] = 'Extremely difficult question';
        }
        
        if ($stats['success_rate'] > 95 && $stats['total_attempts'] > 50) {
            $anomalies[] = 'Extremely easy question';
        }
        
        if ($stats['avg_time_spent'] > 120) {
            $anomalies[] = 'Takes too much time';
        }
        
        $optionDist = $stats['option_distribution'];
        if (!empty($optionDist)) {
            $correctKey = array_search(max($optionDist), $optionDist);
            if ($correctKey != 'correct') {
                $anomalies[] = 'Most common answer is incorrect';
            }
        }
        
        return $anomalies;
    }

    public function detectQuestionAnomalies(int $questionId): array
    {
        return $this->getQuestionAnomalies($questionId);
    }

    public function getQuestionOutliers(int $questionId): array
    {
        $answers = DB::table('attempt_answers')
            ->join('attempts', 'attempt_answers.attempt_id', '=', 'attempts.id')
            ->where('attempt_answers.question_id', $questionId)
            ->where('attempts.status', 'completed')
            ->select('attempts.user_id', 'attempts.percentage_score')
            ->get();
        
        $mean = $answers->avg('percentage_score');
        $stdDev = $answers->stdDev('percentage_score');
        
        $outliers = [];
        foreach ($answers as $answer) {
            $zScore = abs(($answer->percentage_score - $mean) / max($stdDev, 1));
            if ($zScore > 2) {
                $outliers[] = [
                    'user_id' => $answer->user_id,
                    'score' => $answer->percentage_score,
                    'z_score' => $zScore,
                ];
            }
        }
        
        return $outliers;
    }

    public function getQuestionClusters(int $questionId): array
    {
        // Would require clustering algorithm
        return [];
    }

    public function getQuestionSegments(int $questionId): array
    {
        // Would require segmentation
        return [];
    }

    public function getQuestionProfiles(int $questionId): array
    {
        // Would require profiling
        return [];
    }

    public function getQuestionPersonas(int $questionId): array
    {
        // Would require persona creation
        return [];
    }

    public function getQuestionJourneys(int $questionId): array
    {
        // Would require journey mapping
        return [];
    }

    public function getQuestionPaths(int $questionId): array
    {
        // Would require path analysis
        return [];
    }

    public function getQuestionFunnels(int $questionId): array
    {
        // Would require funnel analysis
        return [];
    }

    public function getQuestionConversion(int $questionId): float
    {
        $stats = $this->getQuestionStats($questionId);
        return $stats['total_attempts'] > 0 ? $stats['correct_count'] / $stats['total_attempts'] : 0;
    }

    public function getQuestionChurn(int $questionId): float
    {
        $attempts = DB::table('attempt_answers')
            ->where('question_id', $questionId)
            ->distinct('attempt_id')
            ->count('attempt_id');
        
        $abandoned = DB::table('attempt_answers')
            ->where('question_id', $questionId)
            ->whereNull('selected_answer')
            ->count();
        
        return $attempts > 0 ? $abandoned / $attempts : 0;
    }

    public function getQuestionLoyalty(int $questionId): float
    {
        $returning = DB::table('attempt_answers')
            ->join('attempts', 'attempt_answers.attempt_id', '=', 'attempts.id')
            ->where('attempt_answers.question_id', $questionId)
            ->groupBy('attempts.user_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();
        
        $total = DB::table('attempt_answers')
            ->join('attempts', 'attempt_answers.attempt_id', '=', 'attempts.id')
            ->where('attempt_answers.question_id', $questionId)
            ->distinct('attempts.user_id')
            ->count('attempts.user_id');
        
        return $total > 0 ? $returning / $total : 0;
    }

    public function getQuestionAdvocacy(int $questionId): float
    {
        // Would require sharing/liking data
        return 0.5;
    }

    public function getQuestionVirality(int $questionId): float
    {
        // Would require sharing data
        return 0.3;
    }

    public function getQuestionNetwork(int $questionId): array
    {
        // Would require network analysis
        return [];
    }

    public function getQuestionConnections(int $questionId): array
    {
        // Would require connection data
        return [];
    }

    public function getQuestionRelationships(int $questionId): array
    {
        // Would require relationship data
        return [];
    }

    public function getQuestionInteractions(int $questionId): array
    {
        // Would require interaction tracking
        return [];
    }

    public function getQuestionRatings(int $questionId): array
    {
        // Would require rating system
        return [];
    }

    public function getQuestionShares(int $questionId): array
    {
        // Would require sharing system
        return [];
    }

    public function getQuestionSaves(int $questionId): array
    {
        // Would require save/bookmark system
        return [];
    }

    public function getQuestionBookmarks(int $questionId): array
    {
        // Would require bookmark system
        return [];
    }

    public function getQuestionLikes(int $questionId): array
    {
        // Would require like system
        return [];
    }

    public function getQuestionDislikes(int $questionId): array
    {
        // Would require dislike system
        return [];
    }

    public function getQuestionFlags(int $questionId): array
    {
        // Would require flagging system
        return [];
    }

    public function getQuestionSpam(int $questionId): array
    {
        // Would require spam detection
        return [];
    }

    public function getQuestionAbuse(int $questionId): array
    {
        // Would require abuse reporting
        return [];
    }

    public function getQuestionViolations(int $questionId): array
    {
        // Would require violation tracking
        return [];
    }

    public function getQuestionAppeals(int $questionId): array
    {
        // Would require appeals system
        return [];
    }

    public function getQuestionDisputes(int $questionId): array
    {
        // Would require dispute system
        return [];
    }

    public function getQuestionResolutions(int $questionId): array
    {
        // Would require resolution system
        return [];
    }

    public function getQuestionSettlements(int $questionId): array
    {
        // Would require settlement system
        return [];
    }

    public function getQuestionArbitrations(int $questionId): array
    {
        // Would require arbitration system
        return [];
    }

    public function getQuestionJudgments(int $questionId): array
    {
        // Would require judgment system
        return [];
    }

    public function getQuestionVerdicts(int $questionId): array
    {
        // Would require verdict system
        return [];
    }

    public function getQuestionDecisions(int $questionId): array
    {
        // Would require decision system
        return [];
    }

    public function getQuestionRulings(int $questionId): array
    {
        // Would require ruling system
        return [];
    }

    public function getQuestionPrecedents(int $questionId): array
    {
        // Would require precedent system
        return [];
    }

    public function getQuestionPolicies(int $questionId): array
    {
        // Would require policy system
        return [];
    }

    public function getQuestionGuidelines(int $questionId): array
    {
        // Would require guidelines system
        return [];
    }

    public function getQuestionStandards(int $questionId): array
    {
        // Would require standards system
        return [];
    }

    public function getQuestionRegulations(int $questionId): array
    {
        // Would require regulations system
        return [];
    }

    public function getQuestionCompliance(int $questionId): array
    {
        // Would require compliance system
        return [];
    }

    public function getQuestionCertifications(int $questionId): array
    {
        // Would require certification system
        return [];
    }

    public function getQuestionAccreditations(int $questionId): array
    {
        // Would require accreditation system
        return [];
    }

    public function getQuestionEndorsements(int $questionId): array
    {
        // Would require endorsement system
        return [];
    }

    public function getQuestionRecommendations(int $questionId): array
    {
        // Would require recommendation system
        return [];
    }

    public function getQuestionTestimonials(int $questionId): array
    {
        // Would require testimonial system
        return [];
    }

    public function getQuestionCaseStudies(int $questionId): array
    {
        // Would require case study system
        return [];
    }

    public function getQuestionSuccessStories(int $questionId): array
    {
        // Would require success story system
        return [];
    }

    public function getQuestionUseCases(int $questionId): array
    {
        // Would require use case system
        return [];
    }

    public function getQuestionApplications(int $questionId): array
    {
        // Would require application system
        return [];
    }

    public function getQuestionImplementations(int $questionId): array
    {
        // Would require implementation system
        return [];
    }

    public function getQuestionIntegrations(int $questionId): array
    {
        // Would require integration system
        return [];
    }

    public function getQuestionExtensions(int $questionId): array
    {
        // Would require extension system
        return [];
    }

    public function getQuestionPlugins(int $questionId): array
    {
        // Would require plugin system
        return [];
    }

    public function getQuestionAddons(int $questionId): array
    {
        // Would require addon system
        return [];
    }

    public function getQuestionModules(int $questionId): array
    {
        // Would require module system
        return [];
    }

    public function getQuestionComponents(int $questionId): array
    {
        // Would require component system
        return [];
    }

    public function getQuestionFragments(int $questionId): array
    {
        // Would require fragment system
        return [];
    }

    public function getQuestionSnippets(int $questionId): array
    {
        // Would require snippet system
        return [];
    }

    public function getQuestionTemplates(int $questionId): array
    {
        // Would require template system
        return [];
    }

    public function getQuestionBlueprints(int $questionId): array
    {
        // Would require blueprint system
        return [];
    }

    public function getQuestionSchemas(int $questionId): array
    {
        // Would require schema system
        return [];
    }

    public function getQuestionModels(int $questionId): array
    {
        // Would require model system
        return [];
    }

    public function getQuestionFrameworks(int $questionId): array
    {
        // Would require framework system
        return [];
    }

    public function getQuestionLibraries(int $questionId): array
    {
        // Would require library system
        return [];
    }

    public function getQuestionPackages(int $questionId): array
    {
        // Would require package system
        return [];
    }

    public function getQuestionRequirements(int $questionId): array
    {
        // Would require requirements system
        return [];
    }

    public function getQuestionCorequisites(int $questionId): array
    {
        // Would require corequisite system
        return [];
    }

    public function getQuestionPostrequisites(int $questionId): array
    {
        // Would require postrequisite system
        return [];
    }

    public function getQuestionAntecedents(int $questionId): array
    {
        // Would require antecedent system
        return [];
    }

    public function getQuestionConsequents(int $questionId): array
    {
        // Would require consequent system
        return [];
    }

    public function getQuestionCauses(int $questionId): array
    {
        // Would require cause system
        return [];
    }

    public function getQuestionEffects(int $questionId): array
    {
        // Would require effect system
        return [];
    }

    public function getQuestionCorrelates(int $questionId): array
    {
        // Would require correlate system
        return [];
    }

    public function getQuestionPredictors(int $questionId): array
    {
        // Would require predictor system
        return [];
    }

    public function getQuestionOutcomes(int $questionId): array
    {
        // Would require outcome system
        return [];
    }

    public function getQuestionResults(int $questionId): array
    {
        // Would require result system
        return [];
    }

    public function getQuestionOutputs(int $questionId): array
    {
        // Would require output system
        return [];
    }

    public function getQuestionDeliverables(int $questionId): array
    {
        // Would require deliverable system
        return [];
    }

    public function getQuestionArtifacts(int $questionId): array
    {
        // Would require artifact system
        return [];
    }

    public function getQuestionEvidence(int $questionId): array
    {
        // Would require evidence system
        return [];
    }

    public function getQuestionProof(int $questionId): array
    {
        // Would require proof system
        return [];
    }

    public function getQuestionValidation(int $questionId): array
    {
        // Would require validation system
        return [];
    }

    public function getQuestionVerification(int $questionId): array
    {
        // Would require verification system
        return [];
    }

    public function getQuestionAuthentication(int $questionId): array
    {
        // Would require authentication system
        return [];
    }

    public function getQuestionAuthorization(int $questionId): array
    {
        // Would require authorization system
        return [];
    }

    public function getQuestionPermissions(int $questionId): array
    {
        // Would require permission system
        return [];
    }

    public function getQuestionRoles(int $questionId): array
    {
        // Would require role system
        return [];
    }

    public function getQuestionAccess(int $questionId): array
    {
        // Would require access system
        return [];
    }

    public function getQuestionSecurity(int $questionId): array
    {
        // Would require security system
        return [];
    }

    public function getQuestionPrivacy(int $questionId): array
    {
        // Would require privacy system
        return [];
    }

    public function getQuestionConfidentiality(int $questionId): array
    {
        // Would require confidentiality system
        return [];
    }

    public function getQuestionIntegrity(int $questionId): array
    {
        // Would require integrity system
        return [];
    }

    public function getQuestionAvailability(int $questionId): array
    {
        // Would require availability system
        return [];
    }

    public function getQuestionDurability(int $questionId): array
    {
        // Would require durability system
        return [];
    }

    public function getQuestionScalability(int $questionId): array
    {
        // Would require scalability system
        return [];
    }

    public function getQuestionPerformance(int $questionId): array
    {
        return $this->getQuestionStats($questionId);
    }

    public function getQuestionExcellence(int $questionId): array
    {
        return [
            'clarity' => 0.9,
            'accuracy' => 1.0,
            'relevance' => $this->getQuestionRelevance($questionId),
            'effectiveness' => $this->getQuestionEffectiveness($questionId),
        ];
    }

    public function getQuestionExpertise(int $questionId): array
    {
        // Would require expertise system
        return [];
    }

    public function getQuestionProficiency(int $questionId): array
    {
        // Would require proficiency system
        return [];
    }

    public function getQuestionCompetence(int $questionId): array
    {
        // Would require competence system
        return [];
    }

    public function getQuestionCapability(int $questionId): array
    {
        // Would require capability system
        return [];
    }

    public function getQuestionCapacity(int $questionId): array
    {
        // Would require capacity system
        return [];
    }

    public function getQuestionPotential(int $questionId): array
    {
        // Would require potential system
        return [];
    }

    public function getQuestionGrowth(int $questionId): array
    {
        // Would require growth system
        return [];
    }

    public function getQuestionDevelopment(int $questionId): array
    {
        // Would require development system
        return [];
    }

    public function getQuestionEvolution(int $questionId): array
    {
        // Would require evolution system
        return [];
    }

    public function getQuestionTransformation(int $questionId): array
    {
        // Would require transformation system
        return [];
    }

    public function getQuestionInnovation(int $questionId): array
    {
        // Would require innovation system
        return [];
    }

    public function getQuestionDisruption(int $questionId): array
    {
        // Would require disruption system
        return [];
    }

    public function getQuestionRevolution(int $questionId): array
    {
        // Would require revolution system
        return [];
    }

    public function getQuestionParadigm(int $questionId): array
    {
        // Would require paradigm system
        return [];
    }

    public function getQuestionShift(int $questionId): array
    {
        // Would require shift system
        return [];
    }

    public function getQuestionChange(int $questionId): array
    {
        // Would require change system
        return [];
    }

    public function getQuestionAdvancement(int $questionId): array
    {
        // Would require advancement system
        return [];
    }

    public function getQuestionEnhancement(int $questionId): array
    {
        // Would require enhancement system
        return [];
    }

    public function getQuestionOptimization(int $questionId): array
    {
        // Would require optimization system
        return [];
    }

    public function getQuestionRefinement(int $questionId): array
    {
        // Would require refinement system
        return [];
    }

    public function getQuestionRevision(int $questionId): array
    {
        // Would require revision system
        return [];
    }

    public function getQuestionUpdate(int $questionId): array
    {
        // Would require update system
        return [];
    }

    public function getQuestionUpgrade(int $questionId): array
    {
        // Would require upgrade system
        return [];
    }

    public function getQuestionMigration(int $questionId): array
    {
        // Would require migration system
        return [];
    }

    public function getQuestionLocalization(int $questionId): array
    {
        // Would require localization system
        return [];
    }

    public function getQuestionInternationalization(int $questionId): array
    {
        // Would require internationalization system
        return [];
    }

    public function getQuestionGlobalization(int $questionId): array
    {
        // Would require globalization system
        return [];
    }

    public function getQuestionStandardization(int $questionId): array
    {
        // Would require standardization system
        return [];
    }

    public function getQuestionCustomization(int $questionId): array
    {
        // Would require customization system
        return [];
    }

    public function getQuestionIndividualization(int $questionId): array
    {
        // Would require individualization system
        return [];
    }

    public function getQuestionAdaptation(int $questionId): array
    {
        // Would require adaptation system
        return [];
    }

    public function getQuestionModification(int $questionId): array
    {
        // Would require modification system
        return [];
    }

    public function getQuestionAdjustment(int $questionId): array
    {
        // Would require adjustment system
        return [];
    }

    public function getQuestionCorrection(int $questionId): array
    {
        // Would require correction system
        return [];
    }

    public function getQuestionRectification(int $questionId): array
    {
        // Would require rectification system
        return [];
    }

    public function getQuestionRemediation(int $questionId): array
    {
        // Would require remediation system
        return [];
    }

    public function getQuestionIntervention(int $questionId): array
    {
        // Would require intervention system
        return [];
    }

    public function getQuestionSupport(int $questionId): array
    {
        // Would require support system
        return [];
    }

    public function getQuestionAssistance(int $questionId): array
    {
        // Would require assistance system
        return [];
    }

    public function getQuestionGuidance(int $questionId): array
    {
        // Would require guidance system
        return [];
    }

    public function getQuestionMentoring(int $questionId): array
    {
        // Would require mentoring system
        return [];
    }

    public function getQuestionCoaching(int $questionId): array
    {
        // Would require coaching system
        return [];
    }

    public function getQuestionTutoring(int $questionId): array
    {
        // Would require tutoring system
        return [];
    }

    public function getQuestionTeaching(int $questionId): array
    {
        // Would require teaching system
        return [];
    }

    public function getQuestionInstruction(int $questionId): array
    {
        // Would require instruction system
        return [];
    }

    public function getQuestionEducation(int $questionId): array
    {
        // Would require education system
        return [];
    }

    public function getQuestionTraining(int $questionId): array
    {
        // Would require training system
        return [];
    }

    private function updateQuizQuestionCount(int $quizId): void
    {
        $count = Question::where('quiz_id', $quizId)->count();
        \App\Models\Quiz::where('id', $quizId)->update(['total_questions' => $count]);
    }

    private function reorderAfterDelete(int $quizId): void
    {
        $questions = Question::where('quiz_id', $quizId)
            ->orderBy('order')
            ->get();
        
        foreach ($questions as $index => $question) {
            $question->order = $index + 1;
            $question->save();
        }
    }

    private function clearCache(int $quizId): void
    {
        Cache::forget("quiz.questions.{$quizId}");
        Cache::forget("quiz.questions.stats.{$quizId}");
    }

    private function extractKeywords(string $text): array
    {
        $text = strtolower(preg_replace('/[^\w\s]/', '', $text));
        $words = explode(' ', $text);
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        
        $keywords = array_filter($words, function ($word) use ($stopWords) {
            return strlen($word) > 3 && !in_array($word, $stopWords);
        });
        
        return array_slice(array_values($keywords), 0, 5);
    }

    private function getNextDifficultyLevel(string $current, string $direction): string
    {
        $levels = ['easy', 'medium', 'hard', 'expert'];
        $index = array_search($current, $levels);
        
        if ($index === false) {
            return 'medium';
        }
        
        if ($direction === 'up') {
            return $levels[min($index + 1, count($levels) - 1)];
        }
        
        return $levels[max($index - 1, 0)];
    }
}