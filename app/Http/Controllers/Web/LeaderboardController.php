<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Interfaces\LeaderboardServiceInterface;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function __construct(
        private LeaderboardServiceInterface $leaderboardService
    ) {}

    public function index(Request $request)
    {
        $type = $request->get('type', 'global');
        
        switch ($type) {
            case 'weekly':
                $leaderboard = $this->leaderboardService->getWeeklyLeaderboard(50);
                break;
            case 'monthly':
                $leaderboard = $this->leaderboardService->getMonthlyLeaderboard(50);
                break;
            default:
                $leaderboard = $this->leaderboardService->getGlobalLeaderboard(50);
        }
        
        $userRank = null;
        if (auth()->check()) {
            $userRank = $this->leaderboardService->getUserRank(auth()->id(), $type);
        }
        
        return view('leaderboard.index', compact('leaderboard', 'type', 'userRank'));
    }
}