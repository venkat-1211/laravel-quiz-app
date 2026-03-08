<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Interfaces\AttemptServiceInterface;
use App\Services\Interfaces\QuizServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttemptController extends Controller
{
    public function __construct(
        private AttemptServiceInterface $attemptService,
        private QuizServiceInterface $quizService
    ) {}

    public function start(int $quizId)
    {
        try {
            $quizData = $this->quizService->getQuizForAttempt($quizId, Auth::id());
            $attempt = $this->attemptService->startAttempt(Auth::id(), $quizId);
            
            return view('attempt.take', [
                'quiz' => $quizData['quiz'],
                'questions' => $quizData['questions'],
                'attempt' => $attempt,
            ]);
        } catch (\Exception $e) {
            dd($e->getMessage());
            return redirect()->route('quizzes.show', $quizId)
                ->with('error', $e->getMessage());
        }
    }

    public function submitAnswer(Request $request, int $attemptId)
    {
        $request->validate([
            'question_id' => 'required|integer',
            'answer' => 'nullable|string',
            'time_spent' => 'required|integer',
        ]);

        try {
            $this->attemptService->submitAnswer(
                $attemptId,
                $request->question_id,
                $request->answer,
                $request->time_spent
            );

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function flagQuestion(Request $request, int $attemptId)
    {
        $request->validate([
            'question_id' => 'required|integer',
            'flag' => 'required|boolean',
        ]);

        try {
            $this->attemptService->flagQuestion(
                $attemptId,
                $request->question_id,
                $request->flag
            );

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function complete(int $attemptId)
    {
        try {
            $attempt = $this->attemptService->completeAttempt($attemptId);
            
            return redirect()->route('attempt.results', $attemptId);
        } catch (\Exception $e) {
            return redirect()->route('dashboard')
                ->with('error', $e->getMessage());
        }
    }

    public function results(int $attemptId)
    {
        try {
            $attempt = $this->attemptService->getAttemptResults($attemptId);
            
            return view('attempt.results', compact('attempt'));
        } catch (\Exception $e) {
            return redirect()->route('dashboard')
                ->with('error', $e->getMessage());
        }
    }

    public function review(int $attemptId)
    {
        try {
            $attempt = $this->attemptService->getAttemptResults($attemptId);
            
            // Ensure the attempt belongs to the authenticated user
            if ($attempt->user_id !== Auth::id()) {
                abort(403, 'Unauthorized access.');
            }
            
            return view('attempt.review', compact('attempt'));
        } catch (\Exception $e) {
            return redirect()->route('dashboard')
                ->with('error', 'Unable to load review: ' . $e->getMessage());
        }
    }
}