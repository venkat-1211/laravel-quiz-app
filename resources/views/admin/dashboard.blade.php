@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card stat-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-2">Total Users</h6>
                            <h2 class="mb-0">{{ $totalUsers }}</h2>
                        </div>
                        <div>
                            <i class="bi bi-people-fill" style="font-size: 2.5rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                    <small class="text-white-50">
                        <i class="bi bi-arrow-up"></i> 12% increase
                    </small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stat-card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-2">Total Quizzes</h6>
                            <h2 class="mb-0">{{ $totalQuizzes }}</h2>
                        </div>
                        <div>
                            <i class="bi bi-question-circle-fill" style="font-size: 2.5rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                    <small class="text-white-50">
                        {{ $activeQuizzes }} active quizzes
                    </small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stat-card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-2">Total Attempts</h6>
                            <h2 class="mb-0">{{ $totalAttempts }}</h2>
                        </div>
                        <div>
                            <i class="bi bi-pencil-square" style="font-size: 2.5rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                    <small class="text-white-50">
                        <i class="bi bi-arrow-up"></i> 8% from last month
                    </small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stat-card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-2">Avg. Score</h6>
                            <h2 class="mb-0">72%</h2>
                        </div>
                        <div>
                            <i class="bi bi-graph-up" style="font-size: 2.5rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                    <small class="text-white-50">
                        +5% from last week
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Attempts Per Day (Last 30 Days)</h5>
                </div>
                <div class="card-body">
                    <canvas id="attemptsChart" height="100"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Top Performing Users</h5>
                </div>
                <div class="card-body">
                    @foreach($topUsers as $index => $user)
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <div class="avatar-circle bg-primary text-white">
                                    {{ getInitials($user->name) }}
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">{{ $user->name }}</h6>
                                <small class="text-muted">
                                    Avg: {{ round($user->avg_score, 1) }}% | 
                                    Attempts: {{ $user->attempts_count }}
                                </small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-success">#{{ $index + 1 }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Second Row -->
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Average Score Per Quiz</h5>
                </div>
                <div class="card-body">
                    <canvas id="scoresChart" height="100"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">User Growth</h5>
                </div>
                <div class="card-body">
                    <canvas id="growthChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .stat-card {
        transition: transform 0.3s, box-shadow 0.3s;
        cursor: pointer;
        border: none;
        border-radius: 15px;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    
    .avatar-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 16px;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Attempts Chart
        new Chart(document.getElementById('attemptsChart'), {
            type: 'line',
            data: {
                labels: {!! json_encode($attemptsPerDay['labels']) !!},
                datasets: [{
                    label: 'Attempts',
                    data: {!! json_encode($attemptsPerDay['data']) !!},
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Scores Chart
        new Chart(document.getElementById('scoresChart'), {
            type: 'bar',
            data: {
                labels: {!! json_encode($averageScorePerQuiz['labels']) !!},
                datasets: [{
                    label: 'Average Score (%)',
                    data: {!! json_encode($averageScorePerQuiz['data']) !!},
                    backgroundColor: '#36a2eb',
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Growth Chart
        new Chart(document.getElementById('growthChart'), {
            type: 'line',
            data: {
                labels: {!! json_encode($userGrowth['labels']) !!},
                datasets: [{
                    label: 'New Users',
                    data: {!! json_encode($userGrowth['data']) !!},
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    });
</script>
@endpush