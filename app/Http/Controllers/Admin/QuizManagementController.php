<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Quiz\StoreQuizRequest;
use App\Services\Interfaces\QuizServiceInterface;
use App\Services\Interfaces\CategoryServiceInterface;
use Illuminate\Http\Request;

class QuizManagementController extends Controller
{
    public function __construct(
        private QuizServiceInterface $quizService,
        private CategoryServiceInterface $categoryService
    ) {}

    public function index()
    {
        $quizzes = \App\Models\Quiz::with(['category', 'questions'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return view('admin.quizzes.index', compact('quizzes'));
    }

    public function create()
    {
        $categories = $this->categoryService->getAllActive();
        return view('admin.quizzes.create', compact('categories'));
    }

    public function store(StoreQuizRequest $request)
    {
        $quizDTO = \App\DTOs\QuizDTO::fromArray($request->validated());
        
        try {
            $quiz = $this->quizService->createQuiz($quizDTO);
            
            return redirect()->route('admin.quizzes.edit', $quiz->id)
                ->with('success', 'Quiz created successfully. Now add questions.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error creating quiz: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function edit(int $id)
    {
        $quiz = \App\Models\Quiz::with('questions')->findOrFail($id);
        $categories = $this->categoryService->getAllActive();
        
        return view('admin.quizzes.edit', compact('quiz', 'categories'));
    }

    public function update(StoreQuizRequest $request, int $id)
    {
        $quizDTO = \App\DTOs\QuizDTO::fromArray($request->validated());
        
        try {
            $this->quizService->updateQuiz($id, $quizDTO);
            
            return redirect()->route('admin.quizzes.index')
                ->with('success', 'Quiz updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error updating quiz: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->quizService->deleteQuiz($id);
            
            return redirect()->route('admin.quizzes.index')
                ->with('success', 'Quiz deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error deleting quiz: ' . $e->getMessage());
        }
    }

    public function publish(int $id)
    {
        try {
            $this->quizService->publishQuiz($id);
            
            return redirect()->route('admin.quizzes.index')
                ->with('success', 'Quiz published successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error publishing quiz: ' . $e->getMessage());
        }
    }

    public function unpublish(int $id)
    {
        try {
            $this->quizService->unpublishQuiz($id);
            
            return redirect()->route('admin.quizzes.index')
                ->with('success', 'Quiz unpublished successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error unpublishing quiz: ' . $e->getMessage());
        }
    }
}