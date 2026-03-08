@extends('layouts.app')

@section('title', 'My Profile - Quiz App')

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h2 mb-0">
                    <i class="bi bi-person-circle me-2 text-primary"></i>
                    My Profile
                </h1>
                <div>
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-house-door me-2"></i>
                        Back to Dashboard
                    </a>
                    <a href="{{ route('profile.edit') }}" class="btn btn-primary">
                        <i class="bi bi-pencil-square me-2"></i>
                        Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column - Profile Info -->
        <div class="col-lg-4 mb-4">
            <!-- Profile Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center p-4">
                    <!-- Avatar -->
                    <div class="position-relative d-inline-block mb-3">
                        @if($user->avatar)
                            <img src="{{ $user->avatar }}" 
                                 class="rounded-circle border border-3 border-primary" 
                                 width="120" 
                                 height="120" 
                                 style="object-fit: cover;"
                                 alt="{{ $user->name }}">
                        @else
                            <div class="avatar-circle-large bg-primary text-white mx-auto">
                                {{ getInitials($user->name) }}
                            </div>
                        @endif
                        <span class="position-absolute bottom-0 end-0 bg-success rounded-circle p-2 border border-2 border-white"></span>
                    </div>
                    
                    <h4 class="mb-1">{{ $user->name }}</h4>
                    <p class="text-muted mb-2">
                        <i class="bi bi-envelope me-1"></i>{{ $user->email }}
                    </p>
                    
                    @if($user->bio)
                        <p class="text-muted small mb-3">
                            <i class="bi bi-quote me-1"></i>{{ $user->bio }}
                        </p>
                    @endif
                    
                    <div class="d-flex justify-content-center gap-2 mb-3">
                        @if($user->email_verified_at)
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle me-1"></i>Verified
                            </span>
                        @else
                            <span class="badge bg-warning text-dark">
                                <i class="bi bi-exclamation-triangle me-1"></i>Unverified
                            </span>
                        @endif
                        
                        @if($user->social_type)
                            <span class="badge bg-info">
                                <i class="bi bi-{{ $user->social_type }} me-1"></i>{{ ucfirst($user->social_type) }}
                            </span>
                        @endif
                    </div>
                    
                    <hr>
                    
                    <div class="row text-center">
                        <div class="col-4">
                            <h5 class="mb-0">{{ $user->attempts()->count() }}</h5>
                            <small class="text-muted">Attempts</small>
                        </div>
                        <div class="col-4">
                            <h5 class="mb-0">{{ $user->leaderboard->quizzes_completed ?? 0 }}</h5>
                            <small class="text-muted">Completed</small>
                        </div>
                        <div class="col-4">
                            <h5 class="mb-0">{{ $user->leaderboard->total_points ?? 0 }}</h5>
                            <small class="text-muted">Points</small>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="text-start">
                        <p class="mb-2">
                            <i class="bi bi-calendar-check me-2 text-primary"></i>
                            <strong>Member since:</strong> {{ $user->created_at->format('F d, Y') }}
                        </p>
                        <p class="mb-2">
                            <i class="bi bi-clock-history me-2 text-primary"></i>
                            <strong>Last login:</strong> {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}
                        </p>
                        @if($user->last_login_ip)
                            <p class="mb-0">
                                <i class="bi bi-geo-alt me-2 text-primary"></i>
                                <strong>Last IP:</strong> {{ $user->last_login_ip }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Stats Card -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-bar-chart me-2 text-primary"></i>
                        Quick Stats
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span>Total Attempts</span>
                        <span class="fw-bold">{{ $user->attempts()->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Completed Quizzes</span>
                        <span class="fw-bold">{{ $user->leaderboard->quizzes_completed ?? 0 }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Average Score</span>
                        <span class="fw-bold {{ $user->average_score >= 70 ? 'text-success' : ($user->average_score >= 40 ? 'text-warning' : 'text-danger') }}">
                            {{ round($user->average_score, 1) }}%
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Total Points</span>
                        <span class="fw-bold text-primary">{{ $user->leaderboard->total_points ?? 0 }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Global Rank</span>
                        <span class="fw-bold">#{{ $user->leaderboard->rank ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Activity & Achievements -->
        <div class="col-lg-8">
            <!-- Performance Chart -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up me-2 text-primary"></i>
                        Performance Trend
                    </h5>
                    <select class="form-select form-select-sm w-auto" id="chartPeriod">
                        <option value="7">Last 7 Days</option>
                        <option value="30" selected>Last 30 Days</option>
                        <option value="90">Last 90 Days</option>
                    </select>
                </div>
                <div class="card-body">
                    <canvas id="performanceChart" height="100"></canvas>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history me-2 text-primary"></i>
                        Recent Activity
                    </h5>
                    <a href="{{ route('profile.history') }}" class="btn btn-sm btn-outline-primary">
                        View All <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    @if($recentAttempts->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recentAttempts as $attempt)
                                <div class="list-group-item border-0 py-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">{{ $attempt->quiz->title }}</h6>
                                            <small class="text-muted">
                                                <i class="bi bi-clock me-1"></i>{{ $attempt->created_at->diffForHumans() }}
                                            </small>
                                        </div>
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="badge {{ $attempt->percentage_score >= 70 ? 'bg-success' : 'bg-danger' }} rounded-pill px-3 py-2">
                                                {{ $attempt->percentage_score }}%
                                            </span>
                                            <a href="{{ route('attempt.results', $attempt->id) }}" class="btn btn-sm btn-outline-primary rounded-circle">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-inbox fs-1 text-muted"></i>
                            <p class="text-muted mt-2">No activity yet</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Achievements -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-award me-2 text-primary"></i>
                        Achievements
                    </h5>
                    <a href="{{ route('profile.achievements') }}" class="btn btn-sm btn-outline-primary">
                        View All <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body">
                    @if($user->achievements->count() > 0)
                        <div class="row g-3">
                            @foreach($user->achievements->take(4) as $achievement)
                                <div class="col-md-3 col-6">
                                    <div class="text-center">
                                        <div class="achievement-circle bg-warning mb-2">
                                            <i class="{{ $achievement->icon ?? 'bi-award' }} fs-2"></i>
                                        </div>
                                        <h6 class="small mb-0">{{ $achievement->name }}</h6>
                                        <small class="text-muted">{{ $achievement->pivot->earned_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if($user->achievements->count() > 4)
                            <div class="text-center mt-3">
                                <a href="{{ route('profile.achievements') }}" class="btn btn-link">
                                    View {{ $user->achievements->count() - 4 }} more achievements
                                </a>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-3">
                            <i class="bi bi-award fs-1 text-muted"></i>
                            <p class="text-muted mt-2">No achievements yet. Keep taking quizzes!</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .avatar-circle-large {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 48px;
        margin: 0 auto;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .achievement-circle {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
        color: white;
    }
    
    .card {
        border-radius: 15px;
        transition: all 0.3s;
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important;
    }
    
    .list-group-item {
        transition: all 0.3s;
    }
    
    .list-group-item:hover {
        background-color: #f8f9fa;
        transform: translateX(5px);
    }
    
    .btn-outline-primary.rounded-circle {
        width: 35px;
        height: 35px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    .badge {
        font-weight: 500;
        padding: 0.5rem 1rem;
    }
    
    .form-select-sm {
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        padding: 0.4rem 2rem 0.4rem 1rem;
    }
    
    .form-select-sm:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let performanceChart;
    
    document.addEventListener('DOMContentLoaded', function() {
        loadPerformanceData(30);
        
        document.getElementById('chartPeriod').addEventListener('change', function() {
            loadPerformanceData(this.value);
        });
    });

    function loadPerformanceData(days) {
        fetch(`/profile/performance-data?days=${days}`)
            .then(response => response.json())
            .then(data => {
                if (performanceChart) {
                    performanceChart.destroy();
                }
                createChart(data);
            })
            .catch(error => {
                console.error('Error loading performance data:', error);
                createChart({
                    labels: [],
                    scores: []
                });
            });
    }

    function createChart(data) {
        const ctx = document.getElementById('performanceChart').getContext('2d');
        
        performanceChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels || [],
                datasets: [{
                    label: 'Score (%)',
                    data: data.scores || [],
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
    }
</script>
@endpush