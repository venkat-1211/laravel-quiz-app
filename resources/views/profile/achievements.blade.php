@extends('layouts.app')

@section('title', 'My Achievements - Quiz App')

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h2 mb-0">
                    <i class="bi bi-award me-2 text-primary"></i>
                    My Achievements
                </h1>
                <div>
                    <a href="{{ route('profile.index') }}" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-person me-2"></i>
                        Back to Profile
                    </a>
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-primary">
                        <i class="bi bi-house-door me-2"></i>
                        Dashboard
                    </a>
                </div>
            </div>
            <p class="text-muted mt-2">Track your progress and unlock achievements as you complete quizzes</p>
        </div>
    </div>

    <!-- Stats Summary -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-primary-soft me-3">
                            <i class="bi bi-award text-primary"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Total Achievements</h6>
                            <h3 class="mb-0">{{ $user->achievements->count() }}/{{ $allAchievements->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-success-soft me-3">
                            <i class="bi bi-star-fill text-success"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Completion Rate</h6>
                            <h3 class="mb-0">{{ $allAchievements->count() > 0 ? round(($user->achievements->count() / $allAchievements->count()) * 100, 1) : 0 }}%</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-warning-soft me-3">
                            <i class="bi bi-trophy text-warning"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Next Achievement</h6>
                            <h3 class="mb-0">{{ $nextAchievement->name ?? 'None' }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Overall Progress</h6>
                <span class="text-primary fw-bold">{{ $user->achievements->count() }}/{{ $allAchievements->count() }} Achievements</span>
            </div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar bg-primary" 
                     role="progressbar" 
                     style="width: {{ $allAchievements->count() > 0 ? ($user->achievements->count() / $allAchievements->count()) * 100 : 0 }}%" 
                     aria-valuenow="{{ $user->achievements->count() }}" 
                     aria-valuemin="0" 
                     aria-valuemax="{{ $allAchievements->count() }}">
                </div>
            </div>
        </div>
    </div>

    <!-- Achievements Grid -->
    <div class="row g-4">
        <!-- Earned Achievements -->
        <div class="col-12">
            <h5 class="mb-3">
                <i class="bi bi-check-circle-fill text-success me-2"></i>
                Earned Achievements ({{ $user->achievements->count() }})
            </h5>
            
            @if($user->achievements->count() > 0)
                <div class="row g-4 mb-5">
                    @foreach($user->achievements as $achievement)
                        <div class="col-md-4 col-lg-3">
                            <div class="card achievement-card earned border-0 shadow-sm h-100">
                                <div class="card-body text-center p-4">
                                    <div class="achievement-icon earned mb-3">
                                        <i class="{{ $achievement->icon ?? 'bi-award' }} display-4"></i>
                                    </div>
                                    <h5 class="mb-2">{{ $achievement->name }}</h5>
                                    <p class="small text-muted mb-3">{{ $achievement->description }}</p>
                                    <div class="d-flex justify-content-center align-items-center text-success small">
                                        <i class="bi bi-check-circle-fill me-1"></i>
                                        Earned {{ $achievement->pivot->earned_at->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-4 mb-4">
                    <i class="bi bi-award fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No achievements earned yet. Keep taking quizzes!</p>
                </div>
            @endif
        </div>

        <!-- Locked Achievements -->
        <div class="col-12 mt-4">
            <h5 class="mb-3">
                <i class="bi bi-lock-fill text-secondary me-2"></i>
                Locked Achievements ({{ $allAchievements->count() - $user->achievements->count() }})
            </h5>
            
            @if($allAchievements->count() - $user->achievements->count() > 0)
                <div class="row g-4">
                    @foreach($allAchievements as $achievement)
                        @if(!$user->achievements->contains('id', $achievement->id))
                            <div class="col-md-4 col-lg-3">
                                <div class="card achievement-card locked border-0 shadow-sm h-100">
                                    <div class="card-body text-center p-4">
                                        <div class="achievement-icon locked mb-3">
                                            <i class="{{ $achievement->icon ?? 'bi-award' }} display-4"></i>
                                        </div>
                                        <h5 class="mb-2">{{ $achievement->name }}</h5>
                                        <p class="small text-muted mb-3">{{ $achievement->description }}</p>
                                        
                                        @php
                                            $progress = $achievement->criteria ? calculateAchievementProgress($user, $achievement) : 0;
                                        @endphp
                                        
                                        @if($progress > 0)
                                            <div class="progress mb-2" style="height: 5px;">
                                                <div class="progress-bar bg-info" role="progressbar" style="width: {{ $progress }}%"></div>
                                            </div>
                                            <small class="text-muted">{{ $progress }}% complete</small>
                                        @else
                                            <small class="text-muted">Not started</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="text-center py-4">
                    <i class="bi bi-trophy-fill fs-1 text-warning"></i>
                    <p class="text-muted mt-2">Congratulations! You've unlocked all achievements!</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Achievement Tips -->
    <div class="card border-0 shadow-sm mt-5">
        <div class="card-header bg-white border-0">
            <h5 class="mb-0">
                <i class="bi bi-lightbulb me-2 text-warning"></i>
                Tips to Unlock More Achievements
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="d-flex">
                        <div class="tip-icon bg-primary-soft me-3">
                            <i class="bi bi-trophy text-primary"></i>
                        </div>
                        <div>
                            <h6>Complete More Quizzes</h6>
                            <p class="small text-muted mb-0">Each quiz you complete brings you closer to new achievements</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex">
                        <div class="tip-icon bg-success-soft me-3">
                            <i class="bi bi-star-fill text-success"></i>
                        </div>
                        <div>
                            <h6>Aim for High Scores</h6>
                            <p class="small text-muted mb-0">Perfect scores and high averages unlock special achievements</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex">
                        <div class="tip-icon bg-info-soft me-3">
                            <i class="bi bi-arrow-repeat text-info"></i>
                        </div>
                        <div>
                            <h6>Try Different Categories</h6>
                            <p class="small text-muted mb-0">Explore various quiz topics to earn category-specific achievements</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .stat-card {
        border-radius: 12px;
        transition: all 0.3s;
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .stat-icon i {
        font-size: 1.8rem;
    }
    
    .bg-primary-soft {
        background-color: rgba(102, 126, 234, 0.1);
    }
    
    .bg-success-soft {
        background-color: rgba(40, 167, 69, 0.1);
    }
    
    .bg-warning-soft {
        background-color: rgba(255, 193, 7, 0.1);
    }
    
    .bg-info-soft {
        background-color: rgba(23, 162, 184, 0.1);
    }
    
    .achievement-card {
        border-radius: 15px;
        overflow: hidden;
        transition: all 0.3s;
        position: relative;
    }
    
    .achievement-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.15) !important;
    }
    
    .achievement-card.earned {
        background: linear-gradient(135deg, #f6f9fc 0%, #ffffff 100%);
        border-left: 4px solid #28a745;
    }
    
    .achievement-card.locked {
        background: #f8f9fa;
        opacity: 0.9;
    }
    
    .achievement-card.locked::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: repeating-linear-gradient(45deg, rgba(0,0,0,0.02), rgba(0,0,0,0.02) 10px, rgba(0,0,0,0) 10px, rgba(0,0,0,0) 20px);
        pointer-events: none;
    }
    
    .achievement-icon.earned {
        color: #28a745;
        text-shadow: 0 2px 10px rgba(40, 167, 69, 0.3);
    }
    
    .achievement-icon.locked {
        color: #adb5bd;
        opacity: 0.5;
    }
    
    .tip-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .tip-icon i {
        font-size: 1.3rem;
    }
    
    .progress {
        background-color: #e9ecef;
        border-radius: 5px;
    }
    
    .progress-bar {
        border-radius: 5px;
        transition: width 0.5s ease;
    }
</style>
@endpush

@push('scripts')
<script>
    // Animate progress bars on scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.width = entry.target.getAttribute('aria-valuenow') + '%';
            }
        });
    }, { threshold: 0.5 });

    document.querySelectorAll('.progress-bar').forEach(bar => {
        const value = bar.style.width;
        bar.style.width = '0%';
        bar.setAttribute('aria-valuenow', parseInt(value));
        observer.observe(bar);
    });
</script>
@endpush