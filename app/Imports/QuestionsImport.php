<?php

namespace App\Imports;

use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class QuestionsImport implements ToCollection, WithHeadingRow
{
    private int $quizId;
    private array $errors = [];
    private int $successCount = 0;

    public function __construct(int $quizId)
    {
        $this->quizId = $quizId;
    }

    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        try {
            $quiz = Quiz::findOrFail($this->quizId);
            $currentOrder = $quiz->questions()->max('order') + 1;

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +2 because heading row is 1

                // Validate row data
                $validator = Validator::make($row->toArray(), [
                    'question_text' => 'required|string',
                    'option_a' => 'required|string',
                    'option_b' => 'required|string',
                    'option_c' => 'nullable|string',
                    'option_d' => 'nullable|string',
                    'correct_answer' => 'required|string|in:A,B,C,D',
                    'explanation' => 'nullable|string',
                    'difficulty' => 'required|in:easy,medium,hard',
                    'points' => 'nullable|integer|min:1|max:100',
                ]);

                if ($validator->fails()) {
                    $this->errors[] = "Row {$rowNumber}: " . implode(', ', $validator->errors()->all());
                    continue;
                }

                // Prepare options array
                $options = [];
                if (!empty($row['option_a'])) $options['A'] = $row['option_a'];
                if (!empty($row['option_b'])) $options['B'] = $row['option_b'];
                if (!empty($row['option_c'])) $options['C'] = $row['option_c'];
                if (!empty($row['option_d'])) $options['D'] = $row['option_d'];

                // Create question
                Question::create([
                    'quiz_id' => $this->quizId,
                    'question_text' => $row['question_text'],
                    'options' => $options,
                    'correct_answer' => $row['correct_answer'],
                    'explanation' => $row['explanation'] ?? null,
                    'difficulty' => $row['difficulty'],
                    'points' => $row['points'] ?? 10,
                    'order' => $currentOrder++,
                ]);

                $this->successCount++;
            }

            // Update quiz total questions
            $quiz->updateTotalQuestions();

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }
}