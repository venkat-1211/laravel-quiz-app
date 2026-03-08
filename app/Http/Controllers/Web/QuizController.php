<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Interfaces\QuizServiceInterface;
use App\Services\Interfaces\CategoryServiceInterface;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function __construct(
        private QuizServiceInterface $quizService,
        private CategoryServiceInterface $categoryService
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'category', 'difficulty']);
        $quizzes = $this->quizService->getPublishedQuizzes($filters);
        $categories = $this->categoryService->getAllActive();
        
        return view('quiz.index', compact('quizzes', 'categories'));
    }

    public function show(string $slug)
    {
        $quiz = $this->quizService->getQuizBySlug($slug);
        
        if (!$quiz) {
            abort(404);
        }
        
        return view('quiz.show', compact('quiz'));
    }

    public function byCategory(string $slug)
    {
        $category = $this->categoryService->getBySlug($slug);
        
        if (!$category) {
            abort(404);
        }
        
        $quizzes = $this->quizService->getQuizzesByCategory($category->id);
        
        return view('quiz.category', compact('quizzes', 'category'));
    }
}