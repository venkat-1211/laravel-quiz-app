<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Quiz;
use App\Models\Attempt;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Statistics
        $totalUsers = User::count();
        $totalQuizzes = Quiz::count();
        $totalAttempts = Attempt::count();
        $activeQuizzes = Quiz::where('is_published', true)->count();
        
        // Charts data
        $attemptsPerDay = $this->getAttemptsPerDay();
        $averageScorePerQuiz = $this->getAverageScorePerQuiz();
        $topUsers = $this->getTopUsers();
        $userGrowth = $this->getUserGrowth();
        
        return view('admin.dashboard', compact(
            'totalUsers',
            'totalQuizzes',
            'totalAttempts',
            'activeQuizzes',
            'attemptsPerDay',
            'averageScorePerQuiz',
            'topUsers',
            'userGrowth'
        ));
    }

    private function getAttemptsPerDay()
    {
        $data = Attempt::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date'),
            'data' => $data->pluck('count'),
        ];
    }

    private function getAverageScorePerQuiz()
    {
        $data = Quiz::withCount('attempts')
            ->having('attempts_count', '>', 0)
            ->withAvg('attempts as avg_score', 'percentage_score')
            ->orderBy('attempts_count', 'desc')
            ->limit(10)
            ->get();

        return [
            'labels' => $data->pluck('title'),
            'data' => $data->pluck('avg_score'),
        ];
    }

    private function getTopUsers()
    {
        return User::select('users.*')
            ->withCount('attempts')
            ->withAvg('attempts as avg_score', 'percentage_score')
            ->orderByDesc('avg_score')
            ->limit(10)
            ->get();
    }

    private function getUserGrowth()
    {
        $data = User::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date'),
            'data' => $data->pluck('count'),
        ];
    }
}