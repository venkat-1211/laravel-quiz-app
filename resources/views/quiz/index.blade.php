@extends('layouts.app')

@section('title', 'Browse Quizzes')

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="display-5 fw-bold">Browse Quizzes</h1>
            <p class="text-muted">Test your knowledge with our collection of quizzes</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="GET" action="{{ route('quizzes.index') }}" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Search quizzes..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="difficulty" class="form-select">
                                <option value="">All Difficulties</option>
                                <option value="beginner" {{ request('difficulty') == 'beginner' ? 'selected' : '' }}>Beginner</option>
                                <option value="intermediate" {{ request('difficulty') == 'intermediate' ? 'selected' : '' }}>Intermediate</option>
                                <option value="advanced" {{ request('difficulty') == 'advanced' ? 'selected' : '' }}>Advanced</option>
                                <option value="expert" {{ request('difficulty') == 'expert' ? 'selected' : '' }}>Expert</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search me-2"></i>Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Quizzes Grid -->
    <div class="row g-4">
        @forelse($quizzes as $quiz)
            <div class="col-md-6 col-lg-4">
                <div class="card quiz-card h-100 shadow-sm">
                    @if($quiz->featured_image)
                        <img src="{{ asset('storage/' . $quiz->featured_image) }}" class="card-img-top" alt="{{ $quiz->title }}" style="height: 200px; object-fit: cover;">
                    @else
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                            <i class="bi bi-question-circle text-muted" style="font-size: 3rem;"></i>
                        </div>
                    @endif
                    
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0">{{ $quiz->title }}</h5>
                            <span class="badge {{ getDifficultyBadge($quiz->difficulty) }}">
                                {{ ucfirst($quiz->difficulty) }}
                            </span>
                        </div>
                        
                        <p class="card-text text-muted small mb-3">
                            {{ \Illuminate\Support\Str::limit($quiz->description, 100) }}
                        </p>
                        
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="badge bg-info">
                                <i class="bi bi-clock me-1"></i>{{ $quiz->formatted_time_limit }}
                            </span>
                            <span class="badge bg-secondary">
                                <i class="bi bi-question-circle me-1"></i>{{ $quiz->questions_count }} questions
                            </span>
                            @if($quiz->category)
                                <span class="badge bg-light text-dark">
                                    <i class="bi bi-folder me-1"></i>{{ $quiz->category->name }}
                                </span>
                            @endif
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="bi bi-people me-1"></i>{{ $quiz->attempts_count ?? 0 }} attempts
                            </small>
                            <a href="{{ $quiz->slug ? route('quizzes.show', $quiz->slug) : '#' }}" 
                            class="btn btn-sm btn-primary {{ !$quiz->slug ? 'disabled' : '' }}"
                            {{ !$quiz->slug ? 'tabindex="-1" aria-disabled="true"' : '' }}>
                                Start Quiz <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                    <h4>No quizzes found</h4>
                    <p class="text-muted">Try adjusting your filters or check back later.</p>
                    <a href="{{ route('quizzes.index') }}" class="btn btn-primary">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>Clear Filters
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="row mt-4">
        <div class="col-12">
            {{ $quizzes->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .quiz-card {
        transition: transform 0.3s, box-shadow 0.3s;
        border: none;
        border-radius: 15px;
        overflow: hidden;
    }
    
    .quiz-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;
    }
    
    .card-img-top {
        border-top-left-radius: 15px;
        border-top-right-radius: 15px;
    }
    
    .badge {
        padding: 0.5rem 0.8rem;
        font-weight: 500;
    }
    
    .pagination {
        justify-content: center;
    }
    
    .page-link {
        color: #667eea;
        border-radius: 8px;
        margin: 0 3px;
    }
    
    .page-item.active .page-link {
        background-color: #667eea;
        border-color: #667eea;
    }
</style>
@endpush