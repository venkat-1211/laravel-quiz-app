<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserManagementController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::withCount(['attempts', 'attempts as completed_attempts_count' => function($query) {
                $query->where('status', 'completed');
            }])
            ->withAvg('attempts as average_score', 'percentage_score');
        
        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }
        
        // Filter by role
        if ($request->has('role') && $request->role && $request->role !== 'all') {
            if ($request->role === 'admin') {
                $query->whereHas('roles', function($q) {
                    $q->where('name', 'admin');
                });
            } elseif ($request->role === 'user') {
                $query->whereHas('roles', function($q) {
                    $q->where('name', 'user');
                });
            }
        }
        
        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }
        
        $users = $query->orderBy('created_at', 'desc')->paginate(15);
        
        // Get statistics
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $newUsersToday = User::whereDate('created_at', today())->count();
        $usersWithAttempts = User::has('attempts')->count();
        
        return view('admin.users.index', compact('users', 'totalUsers', 'activeUsers', 'newUsersToday', 'usersWithAttempts'));
    }

    /**
     * Display the specified user.
     */
    public function show($id)
    {
        $user = User::with(['attempts.quiz', 'leaderboard', 'achievements'])
            ->withCount(['attempts', 'attempts as completed_attempts_count' => function($query) {
                $query->where('status', 'completed');
            }])
            ->findOrFail($id);
        
        $recentAttempts = $user->attempts()
            ->with('quiz')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        $performanceData = [
            'labels' => [],
            'scores' => []
        ];
        
        $attempts = $user->attempts()
            ->where('status', 'completed')
            ->orderBy('created_at', 'asc')
            ->limit(20)
            ->get();
        
        foreach ($attempts as $attempt) {
            $performanceData['labels'][] = $attempt->created_at->format('M d');
            $performanceData['scores'][] = $attempt->percentage_score;
        }
        
        $stats = [
            'total_attempts' => $user->attempts()->count(),
            'completed_quizzes' => $user->attempts()->where('status', 'completed')->count(),
            'average_score' => round($user->attempts()->where('status', 'completed')->avg('percentage_score') ?? 0, 2),
            'total_points' => $user->leaderboard->total_points ?? 0,
            'quizzes_passed' => $user->attempts()
                ->where('status', 'completed')
                ->whereRaw('percentage_score >= quizzes.passing_score')
                ->join('quizzes', 'attempts.quiz_id', '=', 'quizzes.id')
                ->count(),
        ];
        
        return view('admin.users.show', compact('user', 'recentAttempts', 'performanceData', 'stats'));
    }

    /**
     * Toggle user active status.
     */
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        
        // Prevent deactivating yourself
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }
        
        $user->is_active = !$user->is_active;
        $user->save();
        
        $status = $user->is_active ? 'activated' : 'deactivated';
        
        return back()->with('success', "User {$status} successfully.");
    }

    /**
     * Remove the specified user.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        
        // Soft delete the user
        $user->delete();
        
        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}