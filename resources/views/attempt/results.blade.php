@extends('layouts.app')

@section('title', 'Quiz Results')

@push('styles')
<style>
    .result-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 20px;
        padding: 40px;
        margin-bottom: 30px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    }
    
    .score-circle {
        width: 200px;
        height: 200px;
        border-radius: 50%;
        background: rgba(255,255,255,0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        position: relative;
    }
    
    .score-circle::before {
        content: '';
        position: absolute;
        width: 180px;
        height: 180px;
        border-radius: 50%;
        background: white;
    }
    
    .score-number {
        position: relative;
        font-size: 3rem;
        font-weight: bold;
        color: #667eea;
        z-index: 1;
    }
    
    .stat-box {
        background: white;
        border-radius: 15px;
        padding: 20px;
        text-align: center;
        color: #333;
        transition: transform 0.3s;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .stat-box:hover {
        transform: translateY(-5px);
    }
    
    .stat-icon {
        font-size: 2rem;
        color: #667eea;
        margin-bottom: 10px;
    }
    
    .stat-value {
        font-size: 2rem;
        font-weight: bold;
        color: #333;
    }
    
    .stat-label {
        color: #666;
        font-size: 0.9rem;
    }
    
    .badge-pass {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        padding: 10px 30px;
        border-radius: 50px;
        font-size: 1.2rem;
    }
    
    .badge-fail {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        padding: 10px 30px;
        border-radius: 50px;
        font-size: 1.2rem;
    }
    
    .action-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .action-buttons .btn {
        padding: 12px 30px;
        border-radius: 50px;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .action-buttons .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
</style>
@endpush

@section('content')
<div class="container py-5">
    <!-- Result Card -->
    <div class="result-card">
        <div class="row align-items-center">
            <div class="col-md-4 text-center">
                <div class="score-circle">
                    <span class="score-number">{{ round($attempt->percentage_score) }}%</span>
                </div>
            </div>
            <div class="col-md-8">
                <h2 class="mb-3">{{ $attempt->quiz['title'] ?? 'Quiz' }}</h2>
                <p class="mb-4">{{ $attempt->quiz['description'] ?? '' }}</p>
                
                <div class="d-flex align-items-center gap-3 mb-4">
                    @if($attempt->percentage_score >= ($attempt->quiz['passing_score'] ?? 70))
                        <div class="badge-pass">
                            <i class="bi bi-trophy-fill me-2"></i>
                            Congratulations! You Passed
                        </div>
                    @else
                        <div class="badge-fail">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            Better Luck Next Time
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="stat-box">
                <div class="stat-icon">
                    <i class="bi bi-check-circle-fill text-success"></i>
                </div>
                <div class="stat-value">{{ $attempt->correct_answers }}</div>
                <div class="stat-label">Correct Answers</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-box">
                <div class="stat-icon">
                    <i class="bi bi-x-circle-fill text-danger"></i>
                </div>
                <div class="stat-value">{{ $attempt->incorrect_answers }}</div>
                <div class="stat-label">Incorrect Answers</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-box">
                <div class="stat-icon">
                    <i class="bi bi-skip-forward-fill text-warning"></i>
                </div>
                <div class="stat-value">{{ $attempt->skipped_answers }}</div>
                <div class="stat-label">Skipped</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-box">
                <div class="stat-icon">
                    <i class="bi bi-clock-history text-info"></i>
                </div>
                <div class="stat-value">{{ formatTime($attempt->time_taken, 'full') }}</div>
                <div class="stat-label">Time Taken</div>
            </div>
        </div>
    </div>

    <!-- Performance Chart -->
    <div class="card shadow-sm mb-5">
        <div class="card-header bg-white">
            <h5 class="mb-0">
                <i class="bi bi-graph-up me-2 text-primary"></i>
                Performance Analysis
            </h5>
        </div>
        <div class="card-body">
            <canvas id="performanceChart" height="100"></canvas>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="{{ route('attempt.review', $attempt->id) }}" class="btn btn-outline-primary">
            <i class="bi bi-eye me-2"></i>
            Review Answers
        </a>
        <a href="{{ route('quizzes.show', $attempt->quiz['slug'] ?? '') }}" class="btn btn-primary">
            <i class="bi bi-arrow-repeat me-2"></i>
            Retry Quiz
        </a>
        <a href="{{ route('leaderboard.index') }}" class="btn btn-outline-success">
            <i class="bi bi-trophy me-2"></i>
            View Leaderboard
        </a>
        <a href="{{ route('quizzes.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-grid me-2"></i>
            More Quizzes
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('performanceChart').getContext('2d');
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Correct ({{ $attempt->correct_answers }})', 
                        'Incorrect ({{ $attempt->incorrect_answers }})', 
                        'Skipped ({{ $attempt->skipped_answers }})'],
                datasets: [{
                    data: [
                        {{ $attempt->correct_answers }},
                        {{ $attempt->incorrect_answers }},
                        {{ $attempt->skipped_answers }}
                    ],
                    backgroundColor: [
                        '#28a745',
                        '#dc3545',
                        '#ffc107'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                cutout: '70%'
            }
        });
    });
</script>
@endpush