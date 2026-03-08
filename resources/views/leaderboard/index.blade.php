@extends('layouts.app')

@section('title', 'Leaderboard')

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h1 class="display-5 fw-bold">
                <i class="bi bi-trophy-fill text-warning me-2"></i>
                Leaderboard
            </h1>
            <p class="text-muted">Top performers from around the world</p>
        </div>
    </div>

    <!-- Period Tabs -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <ul class="nav nav-pills nav-justified" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $type == 'global' ? 'active' : '' }}" 
                               href="{{ route('leaderboard.index', ['type' => 'global']) }}">
                                <i class="bi bi-globe me-2"></i>All Time
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $type == 'weekly' ? 'active' : '' }}" 
                               href="{{ route('leaderboard.index', ['type' => 'weekly']) }}">
                                <i class="bi bi-calendar-week me-2"></i>This Week
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $type == 'monthly' ? 'active' : '' }}" 
                               href="{{ route('leaderboard.index', ['type' => 'monthly']) }}">
                                <i class="bi bi-calendar-month me-2"></i>This Month
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- User Rank Card (if logged in) -->
    @auth
        @if($userRank)
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-2 text-center">
                                    <span class="display-4 fw-bold">#{{ $userRank['rank'] }}</span>
                                </div>
                                <div class="col-md-8">
                                    <h4 class="mb-2">Your Ranking</h4>
                                    <div class="row">
                                        <div class="col-4">
                                            <small>Points</small>
                                            <h5 class="mb-0">{{ $userRank['total_points'] ?? $userRank['weekly_points'] ?? $userRank['monthly_points'] ?? 0 }}</h5>
                                        </div>
                                        <div class="col-4">
                                            <small>Quizzes</small>
                                            <h5 class="mb-0">{{ $userRank['quizzes_completed'] ?? 0 }}</h5>
                                        </div>
                                        <div class="col-4">
                                            <small>Avg Score</small>
                                            <h5 class="mb-0">{{ $userRank['average_score'] ?? 0 }}%</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 text-end">
                                    <i class="bi bi-trophy-fill display-1 opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endauth

    <!-- Leaderboard Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-trophy me-2 text-warning"></i>
                        Top Performers - {{ $type == 'global' ? 'All Time' : ($type == 'weekly' ? 'This Week' : 'This Month') }}
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Rank</th>
                                    <th>User</th>
                                    <th class="text-center">Quizzes</th>
                                    <th class="text-center">Avg Score</th>
                                    <th class="text-center">Points</th>
                                    <th class="text-center">Badges</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($leaderboard as $index => $entry)
                                    <tr class="{{ auth()->check() && auth()->id() == $entry['user']['id'] ? 'table-primary' : '' }}">
                                        <td class="ps-4">
                                            @if($index < 3)
                                                <span class="badge {{ $index == 0 ? 'bg-warning' : ($index == 1 ? 'bg-secondary' : 'bg-bronze') }} text-dark p-2">
                                                    <i class="bi bi-trophy-fill me-1"></i>
                                                    #{{ $entry['rank'] }}
                                                </span>
                                            @else
                                                <span class="fw-bold">#{{ $entry['rank'] }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if(isset($entry['user']['avatar']) && $entry['user']['avatar'])
                                                    <img src="{{ $entry['user']['avatar'] }}" class="rounded-circle me-2" width="40" height="40" alt="{{ $entry['user']['name'] }}">
                                                @else
                                                    <div class="avatar-circle bg-primary text-white me-2">
                                                        {{ getInitials($entry['user']['name']) }}
                                                    </div>
                                                @endif
                                                <div>
                                                    <strong>{{ $entry['user']['name'] }}</strong>
                                                    @if($index == 0)
                                                        <span class="badge bg-warning text-dark ms-2">Champion</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-bold">{{ $entry['quizzes_completed'] }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $entry['average_score'] >= 80 ? 'bg-success' : ($entry['average_score'] >= 60 ? 'bg-info' : 'bg-warning') }}">
                                                {{ $entry['average_score'] }}%
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-bold text-primary">{{ $entry['total_points'] ?? $entry['weekly_points'] ?? $entry['monthly_points'] ?? 0 }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if(isset($entry['badges']) && count($entry['badges']) > 0)
                                                @foreach(array_slice($entry['badges'], 0, 3) as $badge)
                                                    <i class="{{ $badge['icon'] ?? 'bi-award' }} text-warning me-1" title="{{ $badge['name'] ?? 'Badge' }}"></i>
                                                @endforeach
                                                @if(count($entry['badges']) > 3)
                                                    <span class="badge bg-secondary">+{{ count($entry['badges']) - 3 }}</span>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <i class="bi bi-trophy fs-1 text-muted d-block mb-3"></i>
                                            <h5>No leaderboard data available</h5>
                                            <p class="text-muted">Be the first to take a quiz and appear here!</p>
                                            <a href="{{ route('quizzes.index') }}" class="btn btn-primary">
                                                <i class="bi bi-play-circle me-2"></i>Start a Quiz
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Achievement Highlights -->
    @if(isset($leaderboard[0]) && isset($leaderboard[0]['badges']) && count($leaderboard[0]['badges']) > 0)
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="bi bi-award me-2 text-primary"></i>
                            Top Performer Achievements
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-3">
                            @foreach($leaderboard[0]['badges'] as $badge)
                                <div class="text-center" style="width: 100px;">
                                    <div class="badge-circle bg-warning mb-2">
                                        <i class="{{ $badge['icon'] ?? 'bi-award' }} fs-2"></i>
                                    </div>
                                    <small class="d-block text-muted">{{ $badge['name'] ?? 'Achievement' }}</small>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Call to Action -->
    @guest
        <div class="row mt-4">
            <div class="col-12">
                <div class="card bg-light">
                    <div class="card-body text-center py-4">
                        <h4>Want to see your name here?</h4>
                        <p class="text-muted mb-3">Join our community and start taking quizzes to climb the leaderboard!</p>
                        <a href="{{ route('register') }}" class="btn btn-primary me-2">
                            <i class="bi bi-person-plus me-2"></i>Sign Up
                        </a>
                        <a href="{{ route('login') }}" class="btn btn-outline-primary">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endguest
</div>
@endsection

@push('styles')
<style>
    .avatar-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 16px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .bg-bronze {
        background-color: #cd7f32;
        color: white;
    }
    
    .badge-circle {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
    }
    
    .nav-pills .nav-link {
        color: #495057;
        border-radius: 8px;
        padding: 0.8rem;
    }
    
    .nav-pills .nav-link.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .nav-pills .nav-link:not(.active):hover {
        background-color: #f8f9fa;
    }
    
    .table td, .table th {
        vertical-align: middle;
        padding: 1rem 0.75rem;
    }
    
    .table-primary {
        background-color: rgba(102, 126, 234, 0.1) !important;
    }
    
    .table-primary td:first-child {
        border-left: 4px solid #667eea;
    }
</style>
@endpush

@push('scripts')
<script>
    // Optional: Add animation to highlight user's row
    document.addEventListener('DOMContentLoaded', function() {
        const userRow = document.querySelector('.table-primary');
        if (userRow) {
            userRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Add highlight animation
            userRow.style.transition = 'background-color 0.5s';
            userRow.style.backgroundColor = 'rgba(102, 126, 234, 0.2)';
            setTimeout(() => {
                userRow.style.backgroundColor = 'rgba(102, 126, 234, 0.1)';
            }, 1000);
        }
    });
</script>
@endpush