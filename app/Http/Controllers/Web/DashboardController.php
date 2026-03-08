<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Interfaces\AttemptServiceInterface;
use App\Services\Interfaces\LeaderboardServiceInterface;
use App\Services\Interfaces\QuizServiceInterface;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(
        private AttemptServiceInterface $attemptService,
        private LeaderboardServiceInterface $leaderboardService,
        private QuizServiceInterface $quizService
    ) {}

    public function index()
    {
        $user = Auth::user();
        
        // Get user statistics
        $totalAttempts = $user->attempts()->count();
        $completedQuizzes = $user->attempts()->where('status', 'completed')->count();
        $averageScore = $user->average_score;
        $totalPoints = $user->total_points;
        
        // Get recent attempts
        $recentAttempts = $this->attemptService->getUserAttempts($user->id, 5);
        
        // Get available quizzes
        $availableQuizzes = $this->quizService->getPublishedQuizzes([], 6);
        
        // Get leaderboard preview
        $leaderboard = $this->leaderboardService->getGlobalLeaderboard(10);
        
        // Get performance chart data
        $chartData = $this->getPerformanceChartData($user->id);
        
        return view('dashboard.index', compact(
            'totalAttempts',
            'completedQuizzes',
            'averageScore',
            'totalPoints',
            'recentAttempts',
            'availableQuizzes',
            'leaderboard',
            'chartData'
        ));
    }

    private function getPerformanceChartData(int $userId)
    {
        $attempts = \App\Models\Attempt::where('user_id', $userId)
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->limit(10)
            ->get()
            ->reverse();

        return [
            'labels' => $attempts->map(fn($a) => $a->completed_at->format('M d')),
            'scores' => $attempts->map(fn($a) => $a->percentage_score),
        ];
    }
}