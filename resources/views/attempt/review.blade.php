@extends('layouts.app')

@section('title', 'Review Answers')

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-2">Review Answers</h1>
                    <p class="text-muted">{{ $attempt->quiz['title'] ?? 'Quiz' }}</p>
                </div>
                <a href="{{ route('attempt.results', $attempt->id) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Results
                </a>
            </div>
        </div>
    </div>

    <!-- Summary Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row text-center">
                <div class="col-3">
                    <div class="text-success">
                        <i class="bi bi-check-circle-fill fs-1"></i>
                        <h5>{{ $attempt->correct_answers }}</h5>
                        <small>Correct</small>
                    </div>
                </div>
                <div class="col-3">
                    <div class="text-danger">
                        <i class="bi bi-x-circle-fill fs-1"></i>
                        <h5>{{ $attempt->incorrect_answers }}</h5>
                        <small>Incorrect</small>
                    </div>
                </div>
                <div class="col-3">
                    <div class="text-warning">
                        <i class="bi bi-skip-forward-fill fs-1"></i>
                        <h5>{{ $attempt->skipped_answers }}</h5>
                        <small>Skipped</small>
                    </div>
                </div>
                <div class="col-3">
                    <div class="text-info">
                        <i class="bi bi-clock-history fs-1"></i>
                        <h5>{{ floor($attempt->time_taken / 60) }}:{{ str_pad($attempt->time_taken % 60, 2, '0', STR_PAD_LEFT) }}</h5>
                        <small>Time Taken</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Questions Review -->
    <div class="row g-4">
        @foreach($attempt->answers as $index => $answer)
            <div class="col-12">
                <div class="card shadow-sm question-card {{ $answer['is_correct'] ? 'border-success' : ($answer['selected_answer'] ? 'border-danger' : 'border-warning') }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="mb-0">Question {{ $index + 1 }}</h5>
                            <span class="badge {{ $answer['is_correct'] ? 'bg-success' : ($answer['selected_answer'] ? 'bg-danger' : 'bg-warning') }}">
                                {{ $answer['is_correct'] ? 'Correct' : ($answer['selected_answer'] ? 'Incorrect' : 'Skipped') }}
                            </span>
                        </div>
                        
                        <p class="fw-bold mb-3">{{ $answer['question_text'] }}</p>
                        
                        <div class="options-list mb-3">
                            @foreach($answer['options'] as $key => $option)
                                <div class="option-item p-3 mb-2 rounded 
                                    @if($key == $answer['correct_answer']) bg-success text-white 
                                    @elseif($key == $answer['selected_answer'] && $key != $answer['correct_answer']) bg-danger text-white 
                                    @else bg-light @endif">
                                    <div class="d-flex align-items-center">
                                        <span class="option-letter me-2">{{ $key }}</span>
                                        <span>{{ $option }}</span>
                                        @if($key == $answer['correct_answer'])
                                            <i class="bi bi-check-circle-fill ms-auto"></i>
                                        @elseif($key == $answer['selected_answer'] && $key != $answer['correct_answer'])
                                            <i class="bi bi-x-circle-fill ms-auto"></i>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        @if($answer['explanation'])
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Explanation:</strong> {{ $answer['explanation'] }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Action Buttons -->
    <div class="row mt-4">
        <div class="col-12 text-center">
            <a href="{{ route('quizzes.show', $attempt->quiz['slug'] ?? '') }}" class="btn btn-primary me-2">
                <i class="bi bi-arrow-repeat me-2"></i>Retry Quiz
            </a>
            <a href="{{ route('quizzes.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-grid me-2"></i>More Quizzes
            </a>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .question-card {
        border-left-width: 4px;
        transition: transform 0.3s;
    }
    
    .question-card:hover {
        transform: translateX(5px);
    }
    
    .option-item {
        transition: all 0.3s;
    }
    
    .option-letter {
        display: inline-block;
        width: 28px;
        height: 28px;
        line-height: 28px;
        text-align: center;
        background-color: rgba(255,255,255,0.2);
        border-radius: 50%;
        font-weight: bold;
    }
    
    .bg-light .option-letter {
        background-color: #667eea;
        color: white;
    }
</style>
@endpush