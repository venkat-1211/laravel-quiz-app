<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Services\Interfaces\UserServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Achievement;

class ProfileController extends Controller
{
    public function __construct(
        private UserServiceInterface $userService
    ) {}

    public function index()
    {
        $user = auth()->user()->load(['achievements', 'leaderboard']);
        $recentAttempts = $user->attempts()
            ->with('quiz')
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        return view('profile.index', compact('user', 'recentAttempts'));
    }

    public function edit()
    {
        $user = auth()->user();
        return view('profile.edit', compact('user'));
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = auth()->user();
        
        $this->userService->updateProfile($user->id, $request->validated());
        
        return redirect()->route('profile.index')
            ->with('success', 'Profile updated successfully.');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = auth()->user();
        $user->password = Hash::make($request->new_password);
        $user->save();

        return back()->with('success', 'Password changed successfully.');
    }

    public function getPerformanceData(Request $request)
    {
        $days = $request->get('days', 30);
        $startDate = now()->subDays($days);
        
        $attempts = auth()->user()->attempts()
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at')
            ->get();
        
        $labels = [];
        $scores = [];
        
        foreach ($attempts as $attempt) {
            $labels[] = $attempt->created_at->format('M d');
            $scores[] = $attempt->percentage_score;
        }
        
        return response()->json([
            'labels' => $labels,
            'scores' => $scores
        ]);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = auth()->user();
        
        DB::beginTransaction();
        
        try {
            $user->attempts()->delete();
            $user->leaderboard()->delete();
            $user->achievements()->detach();
            
            Auth::logout();
            
            $user->delete();
            
            DB::commit();
            
            return redirect()->route('home')->with('success', 'Your account has been permanently deleted.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete account: ' . $e->getMessage());
        }
    }

    public function history(Request $request)
    {
        $user = auth()->user();
        
        $query = $user->attempts()->with('quiz.category');
        
        if ($request->filled('quiz_id')) {
            $query->where('quiz_id', $request->quiz_id);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $attempts = $query->orderBy('created_at', 'desc')->paginate(15);
        
        $quizzes = \App\Models\Quiz::whereIn('id', $user->attempts()->pluck('quiz_id')->unique())
            ->orderBy('title')
            ->get(['id', 'title']);
        
        return view('profile.history', compact('attempts', 'quizzes'));
    }

    public function achievements()
    {
        $user = auth()->user()->load('achievements');
        $allAchievements = Achievement::all();
        
        $nextAchievement = null;
        $highestProgress = 0;
        
        foreach ($allAchievements as $achievement) {
            if (!$user->achievements->contains('id', $achievement->id)) {
                $progress = calculateAchievementProgress($user, $achievement);
                if ($progress > $highestProgress) {
                    $highestProgress = $progress;
                    $nextAchievement = $achievement;
                }
            }
        }
        
        return view('profile.achievements', compact('user', 'allAchievements', 'nextAchievement'));
    }
}