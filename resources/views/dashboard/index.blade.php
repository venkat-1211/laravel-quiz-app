@extends('layouts.app')

@section('title', 'Dashboard - Quiz App')

@section('header')
    <div class="container-fluid py-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h2 class="h3 mb-0 text-gray-800 d-flex align-items-center">
                    <i class="bi bi-speedometer2 me-2 text-primary"></i>
                    {{ __('Dashboard') }}
                </h2>
            </div>
            @role('admin')
                <div class="col-md-6 text-md-end mt-2 mt-md-0">
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-gradient-primary px-4 py-2">
                        <i class="bi bi-shield-lock-fill me-2"></i>
                        <span class="d-none d-sm-inline">Admin</span> Dashboard
                        <i class="bi bi-arrow-right-circle-fill ms-2"></i>
                    </a>
                </div>
            @endrole
        </div>
    </div>
@endsection

@push('styles')
<style>
    .btn-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.95rem;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .btn-gradient-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    .btn-gradient-primary:active {
        transform: translateY(-1px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
    
    .btn-gradient-primary i {
        font-size: 1.1rem;
        vertical-align: middle;
    }
    
    .btn-gradient-primary::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s ease;
    }
    
    .btn-gradient-primary:hover::before {
        left: 100%;
    }
    
    .text-gray-800 {
        color: #2d3748;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .btn-gradient-primary {
            width: 100%;
            justify-content: center;
            display: inline-flex;
            align-items: center;
        }
        
        .btn-gradient-primary .d-none {
            display: inline-block !important;
        }
    }
    
    /* Optional: Add a subtle background to the header */
    .header-gradient {
        background: linear-gradient(135deg, #f5f7fa 0%, #ffffff 100%);
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
</style>
@endpush

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Welcome Section with Profile Dropdown -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-gradient-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <!-- User Avatar -->
                                <div class="position-relative me-3">
                                    @if(auth()->user()->avatar)
                                        <img src="{{ auth()->user()->avatar }}" 
                                             class="rounded-circle border border-white" 
                                             width="70" 
                                             height="70" 
                                             style="object-fit: cover;"
                                             alt="{{ auth()->user()->name }}">
                                    @else
                                        <div class="avatar-circle-large bg-white text-primary">
                                            {{ getInitials(auth()->user()->name) }}
                                        </div>
                                    @endif
                                    <span class="position-absolute bottom-0 end-0 bg-success rounded-circle p-1 border border-white"></span>
                                </div>
                                <div>
                                    <h2 class="mb-1">Welcome back, {{ auth()->user()->name }}!</h2>
                                    <p class="mb-0 opacity-75">
                                        <i class="bi bi-envelope-fill me-1"></i> {{ auth()->user()->email }}
                                    </p>
                                    <p class="mb-0 mt-1">
                                        <span class="badge bg-white text-primary">
                                            <i class="bi bi-calendar-check me-1"></i>Member since {{ auth()->user()->created_at->format('M Y') }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Profile Dropdown -->
                            <div class="dropdown">
                                <button class="btn btn-light rounded-circle p-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-2" style="min-width: 220px;">
                                    <li>
                                        <a class="dropdown-item py-2" href="{{ route('profile.index') }}">
                                            <i class="bi bi-person-circle me-2 text-primary"></i>
                                            My Profile
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2" href="{{ route('profile.edit') }}">
                                            <i class="bi bi-pencil-square me-2 text-info"></i>
                                            Edit Profile
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2" href="{{ route('profile.history') }}">
                                            <i class="bi bi-clock-history me-2 text-warning"></i>
                                            Quiz History
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2" href="{{ route('profile.achievements') }}">
                                            <i class="bi bi-award me-2 text-success"></i>
                                            Achievements
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item py-2" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                            <i class="bi bi-key me-2 text-secondary"></i>
                                            Change Password
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item py-2 text-danger">
                                                <i class="bi bi-box-arrow-right me-2"></i>
                                                Logout
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('quizzes.index') }}" class="btn btn-light shadow-sm">
                        <i class="bi bi-grid me-2"></i>Browse Quizzes
                    </a>
                    <a href="{{ route('profile.history') }}" class="btn btn-light shadow-sm">
                        <i class="bi bi-clock-history me-2"></i>My History
                    </a>
                    <a href="{{ route('leaderboard.index') }}" class="btn btn-light shadow-sm">
                        <i class="bi bi-trophy me-2"></i>Leaderboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total Attempts</h6>
                                <h3 class="mb-0">{{ $totalAttempts }}</h3>
                                <small class="text-success">
                                    <i class="bi bi-arrow-up"></i> {{ $totalAttempts > 0 ? 'Active' : 'Start now' }}
                                </small>
                            </div>
                            <div class="stat-icon bg-primary-soft">
                                <i class="bi bi-pencil-square text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Completed Quizzes</h6>
                                <h3 class="mb-0">{{ $completedQuizzes }}</h3>
                                <small class="text-info">
                                    <i class="bi bi-check-circle"></i> {{ $completedQuizzes }} finished
                                </small>
                            </div>
                            <div class="stat-icon bg-success-soft">
                                <i class="bi bi-check-circle text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Average Score</h6>
                                <h3 class="mb-0">{{ $averageScore }}%</h3>
                                <small class="text-{{ $averageScore >= 70 ? 'success' : ($averageScore >= 40 ? 'warning' : 'danger') }}">
                                    <i class="bi bi-graph-up"></i> {{ $averageScore >= 70 ? 'Good' : ($averageScore >= 40 ? 'Average' : 'Needs practice') }}
                                </small>
                            </div>
                            <div class="stat-icon bg-info-soft">
                                <i class="bi bi-graph-up text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total Points</h6>
                                <h3 class="mb-0">{{ $totalPoints }}</h3>
                                <small class="text-warning">
                                    <i class="bi bi-star"></i> {{ $totalPoints }} points earned
                                </small>
                            </div>
                            <div class="stat-icon bg-warning-soft">
                                <i class="bi bi-star-fill text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Row -->
        <div class="row">
            <!-- Left Column - Performance Chart & Recent Attempts -->
            <div class="col-lg-8">
                <!-- Performance Chart -->
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0">
                            <i class="bi bi-bar-chart-line me-2 text-primary"></i>
                            Your Performance (Last 10 Attempts)
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="performanceChart" height="100"></canvas>
                    </div>
                </div>

                <!-- Recent Attempts -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history me-2 text-primary"></i>
                            Recent Attempts
                        </h5>
                        <a href="{{ route('profile.history') }}" class="btn btn-sm btn-outline-primary rounded-pill px-4">
                            View All <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                    <div class="card-body">
                        @if(isset($recentAttempts['data']) && count($recentAttempts['data']) > 0)
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Quiz</th>
                                            <th>Score</th>
                                            <th>Correct</th>
                                            <th>Time Taken</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentAttempts['data'] as $attempt)
                                            <tr>
                                                <td>
                                                    <strong>{{ $attempt['quiz']['title'] ?? 'N/A' }}</strong>
                                                </td>
                                                <td>
                                                    <span class="badge {{ $attempt['percentage_score'] >= 70 ? 'bg-success' : 'bg-danger' }} rounded-pill px-3 py-2">
                                                        {{ $attempt['percentage_score'] }}%
                                                    </span>
                                                </td>
                                                <td>{{ $attempt['correct_answers'] }}/{{ $attempt['total_questions'] }}</td>
                                                <td>
                                                    <i class="bi bi-clock me-1"></i>
                                                    {{ floor($attempt['time_taken'] / 60) }}:{{ str_pad($attempt['time_taken'] % 60, 2, '0', STR_PAD_LEFT) }}
                                                </td>
                                                <td>{{ \Carbon\Carbon::parse($attempt['completed_at'])->format('M d, Y') }}</td>
                                                <td>
                                                    <a href="{{ route('attempt.results', $attempt['id']) }}" 
                                                       class="btn btn-sm btn-outline-primary rounded-circle"
                                                       title="View Results">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="{{ route('attempt.review', $attempt['id']) }}" 
                                                       class="btn btn-sm btn-outline-info rounded-circle"
                                                       title="Review Answers">
                                                        <i class="bi bi-journal-check"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <div class="mb-3">
                                    <i class="bi bi-inbox fs-1 text-muted"></i>
                                </div>
                                <h5>No attempts yet</h5>
                                <p class="text-muted mb-4">Start your first quiz to see your progress here!</p>
                                <a href="{{ route('quizzes.index') }}" class="btn btn-primary rounded-pill px-5">
                                    <i class="bi bi-play-circle me-2"></i>Browse Quizzes
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Column - Leaderboard & Available Quizzes -->
            <div class="col-lg-4">
                <!-- Leaderboard Preview -->
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-trophy-fill text-warning me-2"></i>
                            Top Performers
                        </h5>
                        <a href="{{ route('leaderboard.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                            View All
                        </a>
                    </div>
                    <div class="card-body p-0">
                        @if(count($leaderboard) > 0)
                            <div class="list-group list-group-flush">
                                @foreach($leaderboard as $index => $entry)
                                    <div class="list-group-item border-0 d-flex align-items-center py-3">
                                        <div class="rank-badge me-3 {{ $index < 3 ? 'bg-warning text-dark' : 'bg-light' }}">
                                            #{{ $entry['rank'] }}
                                        </div>
                                        <div class="flex-shrink-0">
                                            @if(isset($entry['user']['avatar']) && $entry['user']['avatar'])
                                                <img src="{{ $entry['user']['avatar'] }}" 
                                                     class="rounded-circle" 
                                                     width="45" 
                                                     height="45" 
                                                     style="object-fit: cover;"
                                                     alt="{{ $entry['user']['name'] }}">
                                            @else
                                                <div class="avatar-circle bg-primary text-white">
                                                    {{ getInitials($entry['user']['name']) }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1">{{ $entry['user']['name'] }}</h6>
                                            <small class="text-muted">
                                                <i class="bi bi-trophy"></i> {{ $entry['quizzes_completed'] }} quizzes
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <strong class="text-primary">{{ $entry['total_points'] }}</strong>
                                            <small class="d-block text-muted">pts</small>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <p class="text-muted">No data available</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Available Quizzes -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0">
                            <i class="bi bi-question-circle me-2 text-primary"></i>
                            Recommended Quizzes
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        @if($availableQuizzes->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($availableQuizzes as $quiz)
                                    <div class="list-group-item border-0 py-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-2">{{ $quiz->title }}</h6>
                                                <div class="mb-2">
                                                    <span class="badge {{ getDifficultyBadge($quiz->difficulty) }} me-1">
                                                        {{ ucfirst($quiz->difficulty) }}
                                                    </span>
                                                    <span class="badge bg-info me-1">
                                                        <i class="bi bi-clock"></i> {{ $quiz->formatted_time_limit }}
                                                    </span>
                                                    <span class="badge bg-secondary">
                                                        <i class="bi bi-question-circle"></i> {{ $quiz->questions_count }}
                                                    </span>
                                                </div>
                                                <p class="small text-muted mb-0">
                                                    {{ \Illuminate\Support\Str::limit($quiz->description, 50) }}
                                                </p>
                                            </div>
                                            <a href="{{ route('quizzes.show', $quiz->slug) }}" 
                                               class="btn btn-sm btn-primary rounded-circle"
                                               title="Start Quiz">
                                                <i class="bi bi-play-fill"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <p class="text-muted">No quizzes available</p>
                            </div>
                        @endif
                    </div>
                    <div class="card-footer bg-white border-0 text-center py-3">
                        <a href="{{ route('quizzes.index') }}" class="btn btn-outline-primary btn-sm rounded-pill px-4">
                            Browse All Quizzes <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title" id="changePasswordModalLabel">
                    <i class="bi bi-key-fill me-2"></i>
                    Change Password
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST" action="{{ route('profile.change-password') }}" id="changePasswordForm">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password" 
                                   class="form-control border-start-0 @error('current_password') is-invalid @enderror" 
                                   id="current_password" 
                                   name="current_password" 
                                   required>
                            <button class="btn btn-light border" type="button" onclick="togglePasswordField('current_password', 'currentToggleIcon')">
                                <i class="bi bi-eye" id="currentToggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password" 
                                   class="form-control border-start-0 @error('new_password') is-invalid @enderror" 
                                   id="new_password" 
                                   name="new_password" 
                                   required>
                            <button class="btn btn-light border" type="button" onclick="togglePasswordField('new_password', 'newToggleIcon')">
                                <i class="bi bi-eye" id="newToggleIcon"></i>
                            </button>
                        </div>
                        <div class="password-strength mt-2" id="newPasswordStrength"></div>
                        <div class="small text-muted mt-1">
                            <i class="bi bi-info-circle"></i> Minimum 8 characters with at least 1 number and 1 uppercase letter
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="new_password_confirmation" class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password" 
                                   class="form-control border-start-0" 
                                   id="new_password_confirmation" 
                                   name="new_password_confirmation" 
                                   required>
                            <button class="btn btn-light border" type="button" onclick="togglePasswordField('new_password_confirmation', 'confirmToggleIcon')">
                                <i class="bi bi-eye" id="confirmToggleIcon"></i>
                            </button>
                        </div>
                        <div id="passwordMatchMessage" class="small mt-1"></div>
                    </div>

                    <div class="alert alert-info bg-light border-0">
                        <i class="bi bi-shield-lock me-2"></i>
                        Your password is encrypted and secure. We recommend using a strong password.
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>Cancel
                </button>
                <button type="submit" form="changePasswordForm" class="btn btn-primary px-4" id="changePasswordBtn">
                    <i class="bi bi-check-circle me-2"></i>Update Password
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .stat-card {
        transition: all 0.3s;
        border-radius: 15px;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important;
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .stat-icon i {
        font-size: 2rem;
    }
    
    .bg-primary-soft {
        background-color: rgba(102, 126, 234, 0.1);
    }
    
    .bg-success-soft {
        background-color: rgba(40, 167, 69, 0.1);
    }
    
    .bg-info-soft {
        background-color: rgba(23, 162, 184, 0.1);
    }
    
    .bg-warning-soft {
        background-color: rgba(255, 193, 7, 0.1);
    }
    
    .rank-badge {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 14px;
    }
    
    .avatar-circle {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 18px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .avatar-circle-large {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 28px;
        background: white;
        color: #667eea;
    }
    
    .dropdown-menu {
        border-radius: 12px;
        padding: 8px 0;
    }
    
    .dropdown-item {
        padding: 10px 20px;
        font-size: 14px;
        transition: all 0.2s;
    }
    
    .dropdown-item:hover {
        background-color: #f8f9fa;
        transform: translateX(5px);
    }
    
    .dropdown-item i {
        width: 20px;
    }
    
    .btn-light {
        background: white;
        border: none;
        transition: all 0.3s;
    }
    
    .btn-light:hover {
        background: #f8f9fa;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1) !important;
    }
    
    .password-strength {
        height: 4px;
        border-radius: 2px;
        transition: all 0.3s;
    }
    
    .strength-weak {
        background: linear-gradient(90deg, #dc3545 33%, #e9ecef 33%);
    }
    
    .strength-medium {
        background: linear-gradient(90deg, #ffc107 66%, #e9ecef 66%);
    }
    
    .strength-strong {
        background: #28a745;
    }
    
    .modal-content {
        border-radius: 20px;
    }
    
    .modal-header {
        border-radius: 20px 20px 0 0;
        padding: 20px 25px;
    }
    
    .modal-body {
        padding: 25px;
    }
    
    .modal-footer {
        border-radius: 0 0 20px 20px;
        padding: 15px 25px;
    }
    
    .input-group-text {
        border-radius: 10px 0 0 10px;
    }
    
    .input-group .form-control {
        border-radius: 0 10px 10px 0;
    }
    
    .btn.rounded-circle {
        width: 45px;
        height: 45px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
</style>
@endpush

@push('scripts')
<script>
    // Performance Chart
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('performanceChart').getContext('2d');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($chartData['labels'] ?? []) !!},
                datasets: [{
                    label: 'Score (%)',
                    data: {!! json_encode($chartData['scores'] ?? []) !!},
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Score: ${context.raw}%`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            display: true,
                            color: 'rgba(0,0,0,0.03)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    });

    // Password visibility toggle
    function togglePasswordField(fieldId, iconId) {
        const field = document.getElementById(fieldId);
        const icon = document.getElementById(iconId);
        
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }

    // Password strength checker
    document.getElementById('new_password')?.addEventListener('input', function() {
        const password = this.value;
        const strengthDiv = document.getElementById('newPasswordStrength');
        
        const hasUpperCase = /[A-Z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        const hasMinLength = password.length >= 8;
        
        const requirements = [hasMinLength, hasUpperCase, hasNumber];
        const metCount = requirements.filter(Boolean).length;
        
        if (password.length === 0) {
            strengthDiv.className = 'password-strength';
        } else if (metCount <= 1) {
            strengthDiv.className = 'password-strength strength-weak';
        } else if (metCount === 2) {
            strengthDiv.className = 'password-strength strength-medium';
        } else {
            strengthDiv.className = 'password-strength strength-strong';
        }
        
        checkPasswordMatch();
    });

    // Password match checker
    document.getElementById('new_password_confirmation')?.addEventListener('input', checkPasswordMatch);
    document.getElementById('new_password')?.addEventListener('input', checkPasswordMatch);

    function checkPasswordMatch() {
        const password = document.getElementById('new_password')?.value;
        const confirm = document.getElementById('new_password_confirmation')?.value;
        const message = document.getElementById('passwordMatchMessage');
        
        if (confirm && confirm.length > 0) {
            if (password === confirm) {
                message.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i> <span class="text-success">Passwords match</span>';
            } else {
                message.innerHTML = '<i class="bi bi-x-circle-fill text-danger me-1"></i> <span class="text-danger">Passwords do not match</span>';
            }
        } else {
            message.innerHTML = '';
        }
    }

    // Form submission with loading state
    document.getElementById('changePasswordForm')?.addEventListener('submit', function(e) {
        const password = document.getElementById('new_password').value;
        const confirm = document.getElementById('new_password_confirmation').value;
        
        if (password !== confirm) {
            e.preventDefault();
            alert('Passwords do not match!');
            return;
        }
        
        const button = document.getElementById('changePasswordBtn');
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
        button.disabled = true;
    });

    // Tooltip initialization
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Toastr notifications
    @if(session('success'))
        toastr.success('{{ session('success') }}');
    @endif
    
    @if(session('error'))
        toastr.error('{{ session('error') }}');
    @endif
    
    @if(session('info'))
        toastr.info('{{ session('info') }}');
    @endif
    
    @if(session('warning'))
        toastr.warning('{{ session('warning') }}');
    @endif
</script>

<!-- Toastr CSS and JS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
    toastr.options = {
        positionClass: 'toast-top-right',
        progressBar: true,
        timeOut: 3000,
        closeButton: true
    };
</script>
@endpush