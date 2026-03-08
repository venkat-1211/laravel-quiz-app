<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ReportController extends Controller
{
    public function index()
    {
        return view('admin.reports.index');
    }

    public function userPerformance(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->get('start_date', now()->subDays(30));
        $endDate = $request->get('end_date', now());

        $users = Cache::remember('report.user_performance.' . md5($startDate . $endDate), 3600, function () use ($startDate, $endDate) {
            return User::select(
                    'users.id',
                    'users.name',
                    'users.email',
                    DB::raw('COUNT(DISTINCT attempts.id) as total_attempts'),
                    DB::raw('AVG(attempts.percentage_score) as avg_score'),
                    DB::raw('SUM(attempts.score) as total_points'),
                    DB::raw('COUNT(DISTINCT CASE WHEN attempts.percentage_score >= quizzes.passing_score THEN attempts.id END) as passed_quizzes')
                )
                ->leftJoin('attempts', 'users.id', '=', 'attempts.user_id')
                ->leftJoin('quizzes', 'attempts.quiz_id', '=', 'quizzes.id')
                ->whereBetween('attempts.created_at', [$startDate, $endDate])
                ->where('attempts.status', 'completed')
                ->groupBy('users.id', 'users.name', 'users.email')
                ->orderByDesc('avg_score')
                ->paginate(20);
        });

        return view('admin.reports.user-performance', compact('users', 'startDate', 'endDate'));
    }

    public function quizAnalytics(Request $request)
    {
        $request->validate([
            'quiz_id' => 'required|exists:quizzes,id',
        ]);

        $quiz = Quiz::with('category')->findOrFail($request->quiz_id);

        $analytics = Cache::remember('report.quiz_analytics.' . $quiz->id, 3600, function () use ($quiz) {
            $totalAttempts = $quiz->attempts()->where('status', 'completed')->count();
            
            $averageScore = $quiz->attempts()
                ->where('status', 'completed')
                ->avg('percentage_score');

            $passRate = $quiz->attempts()
                ->where('status', 'completed')
                ->whereRaw('percentage_score >= quizzes.passing_score')
                ->count() / max($totalAttempts, 1) * 100;

            $completionRate = $quiz->attempts()
                ->where('status', 'completed')
                ->count() / max($quiz->attempts()->count(), 1) * 100;

            $questionAnalytics = DB::table('attempt_answers')
                ->join('questions', 'attempt_answers.question_id', '=', 'questions.id')
                ->where('questions.quiz_id', $quiz->id)
                ->select(
                    'questions.id',
                    'questions.question_text',
                    DB::raw('COUNT(*) as total_answers'),
                    DB::raw('SUM(CASE WHEN attempt_answers.is_correct = 1 THEN 1 ELSE 0 END) as correct_count'),
                    DB::raw('AVG(attempt_answers.time_spent) as avg_time_spent')
                )
                ->groupBy('questions.id', 'questions.question_text')
                ->get();

            return [
                'total_attempts' => $totalAttempts,
                'average_score' => round($averageScore, 2),
                'pass_rate' => round($passRate, 2),
                'completion_rate' => round($completionRate, 2),
                'question_analytics' => $questionAnalytics,
            ];
        });

        return view('admin.reports.quiz-analytics', compact('quiz', 'analytics'));
    }

    public function exportUserReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $users = User::select(
                'users.name',
                'users.email',
                DB::raw('COUNT(DISTINCT attempts.id) as total_attempts'),
                DB::raw('AVG(attempts.percentage_score) as avg_score'),
                DB::raw('SUM(attempts.score) as total_points')
            )
            ->leftJoin('attempts', 'users.id', '=', 'attempts.user_id')
            ->whereBetween('attempts.created_at', [$request->start_date, $request->end_date])
            ->where('attempts.status', 'completed')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('avg_score')
            ->get();

        $filename = 'user-performance-' . now()->format('Y-m-d') . '.csv';
        $handle = fopen('php://output', 'w');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        fputcsv($handle, ['Name', 'Email', 'Total Attempts', 'Average Score (%)', 'Total Points']);

        foreach ($users as $user) {
            fputcsv($handle, [
                $user->name,
                $user->email,
                $user->total_attempts,
                round($user->avg_score, 2),
                $user->total_points,
            ]);
        }

        fclose($handle);
        exit;
    }

    public function getChartData(Request $request)
    {
        $type = $request->get('type', 'attempts');

        switch ($type) {
            case 'attempts':
                $data = $this->getAttemptsChartData();
                break;
            case 'scores':
                $data = $this->getScoreDistributionData();
                break;
            case 'growth':
                $data = $this->getUserGrowthData();
                break;
            default:
                $data = [];
        }

        return response()->json($data);
    }

    private function getAttemptsChartData()
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
            'datasets' => [
                [
                    'label' => 'Quiz Attempts',
                    'data' => $data->pluck('count'),
                    'borderColor' => '#667eea',
                    'backgroundColor' => 'rgba(102, 126, 234, 0.1)',
                ]
            ]
        ];
    }

    private function getScoreDistributionData()
    {
        $ranges = [
            '0-20%' => [0, 20],
            '21-40%' => [21, 40],
            '41-60%' => [41, 60],
            '61-80%' => [61, 80],
            '81-100%' => [81, 100],
        ];

        $data = [];
        foreach ($ranges as $label => $range) {
            $count = Attempt::where('status', 'completed')
                ->whereBetween('percentage_score', [$range[0], $range[1]])
                ->count();
            
            $data[$label] = $count;
        }

        return [
            'labels' => array_keys($data),
            'datasets' => [
                [
                    'label' => 'Number of Users',
                    'data' => array_values($data),
                    'backgroundColor' => [
                        '#ff6384',
                        '#36a2eb',
                        '#ffce56',
                        '#4bc0c0',
                        '#9966ff'
                    ],
                ]
            ]
        ];
    }

    private function getUserGrowthData()
    {
        $data = User::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $cumulative = 0;
        $cumulativeData = $data->map(function ($item) use (&$cumulative) {
            $cumulative += $item->count;
            return $cumulative;
        });

        return [
            'labels' => $data->pluck('date'),
            'datasets' => [
                [
                    'label' => 'New Users',
                    'data' => $data->pluck('count'),
                    'borderColor' => '#28a745',
                    'backgroundColor' => 'rgba(40, 167, 69, 0.1)',
                ],
                [
                    'label' => 'Total Users',
                    'data' => $cumulativeData,
                    'borderColor' => '#17a2b8',
                    'backgroundColor' => 'rgba(23, 162, 184, 0.1)',
                ]
            ]
        ];
    }
}