@extends('layouts.app')

@section('title', $quiz->title . ' - Quiz Details')

@section('content')
<div class="container py-4">
    <!-- Back Button -->
    <div class="row mb-4">
        <div class="col-12">
            <a href="{{ route('quizzes.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Quizzes
            </a>
        </div>
    </div>

    <!-- Quiz Details -->
    <div class="row">
        <div class="col-lg-8">
            <!-- Main Quiz Card -->
            <div class="card shadow-sm mb-4">
                @if($quiz->featured_image)
                    <img src="{{ asset('storage/' . $quiz->featured_image) }}" class="card-img-top" alt="{{ $quiz->title }}" style="height: 400px; object-fit: cover;">
                @endif
                
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h1 class="h2 mb-0">{{ $quiz->title }}</h1>
                        <span class="badge {{ getDifficultyBadge($quiz->difficulty) }} fs-6">
                            {{ ucfirst($quiz->difficulty) }}
                        </span>
                    </div>
                    
                    @if(isset($quiz->category) && $quiz->category)
                        <div class="mb-3">
                            <span class="badge bg-light text-dark">
                                <i class="bi bi-folder me-1"></i>{{ $quiz->category['name'] ?? $quiz->category->name ?? 'Uncategorized' }}
                            </span>
                        </div>
                    @endif
                    
                    <p class="lead mb-4">{{ $quiz->description }}</p>
                    
                    <div class="row g-4 mb-4">
                        <div class="col-md-3 col-6">
                            <div class="text-center">
                                <i class="bi bi-clock fs-2 text-primary"></i>
                                <h6 class="mt-2">Time Limit</h6>
                                <p class="fw-bold">{{ formatTimeLimit($quiz->time_limit) }}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="text-center">
                                <i class="bi bi-question-circle fs-2 text-success"></i>
                                <h6 class="mt-2">Questions</h6>
                                <p class="fw-bold">{{ $quiz->questions_count ?? $quiz->total_questions }}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="text-center">
                                <i class="bi bi-trophy fs-2 text-warning"></i>
                                <h6 class="mt-2">Passing Score</h6>
                                <p class="fw-bold">{{ $quiz->passing_score }}%</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="text-center">
                                <i class="bi bi-star fs-2 text-info"></i>
                                <h6 class="mt-2">Points</h6>
                                <p class="fw-bold">{{ $quiz->points_per_question }} per question</p>
                            </div>
                        </div>
                    </div>
                    
                    @auth
                        <div class="d-grid gap-2">
                            <a href="{{ route('quiz.start', $quiz->id) }}" class="btn btn-primary btn-lg">
                                <i class="bi bi-play-circle me-2"></i>Start Quiz
                            </a>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Please <a href="{{ route('login') }}">login</a> to take this quiz.
                        </div>
                    @endauth
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Stats Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up me-2 text-primary"></i>
                        Quiz Statistics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span>Total Attempts</span>
                        <span class="fw-bold">{{ $quiz->attempts_count ?? 0 }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Average Score</span>
                        <span class="fw-bold">{{ $quiz->average_score ?? 0 }}%</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Completion Rate</span>
                        <span class="fw-bold">{{ $quiz->completion_rate ?? 0 }}%</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Times Favorited</span>
                        <span class="fw-bold">{{ $quiz->favorites_count ?? 0 }}</span>
                    </div>
                </div>
            </div>

            <!-- Instructions Card -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-info-circle me-2 text-primary"></i>
                        Instructions
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            Read each question carefully
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            You can flag questions for review
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            Timer starts when you begin
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            Results shown after completion
                        </li>
                        @if(isset($quiz->max_attempts) && $quiz->max_attempts > 0)
                            <li class="mb-2">
                                <i class="bi bi-exclamation-circle-fill text-warning me-2"></i>
                                Maximum attempts: {{ $quiz->max_attempts }}
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .card {
        border: none;
        border-radius: 15px;
    }
    
    .card-header {
        border-top-left-radius: 15px !important;
        border-top-right-radius: 15px !important;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
</style>
@endpush