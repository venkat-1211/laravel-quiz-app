<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Question\StoreQuestionRequest;
use App\Http\Requests\Question\BulkUploadQuestionRequest;
use App\Services\Interfaces\QuestionServiceInterface;
use App\Imports\QuestionsImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuestionManagementController extends Controller
{
    public function __construct(
        private QuestionServiceInterface $questionService
    ) {}

    public function index(int $quizId)
    {
        $quiz = \App\Models\Quiz::findOrFail($quizId);
        $questions = $quiz->questions()->orderBy('order')->paginate(20);
        
        return view('admin.questions.index', compact('quiz', 'questions'));
    }

    public function create(int $quizId)
    {
        $quiz = \App\Models\Quiz::findOrFail($quizId);
        
        return view('admin.questions.create', compact('quiz'));
    }

    public function store(StoreQuestionRequest $request, int $quizId)
    {
        $data = $request->validated();
        $data['quiz_id'] = $quizId;
        
        try {
            $this->questionService->createQuestion($data);
            
            return redirect()->route('admin.quizzes.questions.index', $quizId)
                ->with('success', 'Question added successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error adding question: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function edit(int $quizId, int $questionId)
    {
        $quiz = \App\Models\Quiz::findOrFail($quizId);
        $question = \App\Models\Question::findOrFail($questionId);
        
        return view('admin.questions.edit', compact('quiz', 'question'));
    }

    public function update(StoreQuestionRequest $request, int $quizId, int $questionId)
    {
        try {
            $this->questionService->updateQuestion($questionId, $request->validated());
            
            return redirect()->route('admin.quizzes.questions.index', $quizId)
                ->with('success', 'Question updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error updating question: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(int $quizId, int $questionId)
    {
        try {
            $this->questionService->deleteQuestion($questionId);
            
            return redirect()->route('admin.quizzes.questions.index', $quizId)
                ->with('success', 'Question deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error deleting question: ' . $e->getMessage());
        }
    }

    public function bulkUpload(BulkUploadQuestionRequest $request, int $quizId)
    {
        try {
            $result = $this->questionService->bulkUploadFromCsv($quizId, $request->file('file'));
            
            if ($result['success']) {
                return redirect()->route('admin.quizzes.questions.index', $quizId)
                    ->with('success', $result['message']);
            } else {
                return back()->with('error', $result['message'])
                    ->with('import_errors', $result['errors'] ?? []);
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Error uploading questions: ' . $e->getMessage());
        }
    }

    public function reorder(Request $request, int $quizId)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*.id' => 'required|integer',
            'order.*.order' => 'required|integer',
        ]);

        try {
            $this->questionService->updateOrder($quizId, $request->order);
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function bulkDelete(Request $request, int $quizId)
    {
        $request->validate([
            'question_ids' => 'required|array',
            'question_ids.*' => 'exists:questions,id'
        ]);

        try {
            $deleted = $this->questionService->bulkDelete($request->question_ids);
            
            return response()->json([
                'success' => true,
                'deleted' => $deleted,
                'message' => "{$deleted} question(s) deleted successfully."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting questions: ' . $e->getMessage()
            ], 400);
        }
    }

    public function bulkDuplicate(Request $request, int $quizId)
    {
        $request->validate([
            'question_ids' => 'required|array',
            'question_ids.*' => 'exists:questions,id'
        ]);

        try {
            $duplicated = $this->questionService->bulkDuplicate($request->question_ids, $quizId);
            
            return response()->json([
                'success' => true,
                'duplicated' => $duplicated,
                'message' => "{$duplicated} question(s) duplicated successfully."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error duplicating questions: ' . $e->getMessage()
            ], 400);
        }
    }

    public function preview(int $questionId)
    {
        $question = \App\Models\Question::with('quiz')->findOrFail($questionId);
        
        return view('admin.questions.preview', compact('question'));
    }

    public function duplicate(int $questionId)
    {
        try {
            $newQuestion = $this->questionService->duplicateQuestion($questionId);
            
            return response()->json([
                'success' => true,
                'message' => 'Question duplicated successfully.',
                'question_id' => $newQuestion->id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error duplicating question: ' . $e->getMessage()
            ], 400);
        }
    }
}