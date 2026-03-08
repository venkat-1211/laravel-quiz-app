@extends('layouts.admin')

@section('title', 'Create Quiz')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Create New Quiz</h2>
        <a href="{{ route('admin.quizzes.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>
            Back to List
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.quizzes.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="row">
                    <div class="col-md-8">
                        <!-- Basic Information -->
                        <div class="mb-3">
                            <label for="title" class="form-label">Quiz Title <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('title') is-invalid @enderror" 
                                   id="title" 
                                   name="title" 
                                   value="{{ old('title') }}"
                                   required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="4"
                                      required>{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="featured_image" class="form-label">Featured Image</label>
                            <input type="file" 
                                   class="form-control @error('featured_image') is-invalid @enderror" 
                                   id="featured_image" 
                                   name="featured_image"
                                   accept="image/*">
                            @error('featured_image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Recommended size: 1200x600px (Max 2MB)</small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <!-- Settings -->
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Quiz Settings</h6>
                                
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                    <select class="form-select @error('category_id') is-invalid @enderror" 
                                            id="category_id" 
                                            name="category_id"
                                            required>
                                        <option value="">Select Category</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="difficulty" class="form-label">Difficulty <span class="text-danger">*</span></label>
                                    <select class="form-select @error('difficulty') is-invalid @enderror" 
                                            id="difficulty" 
                                            name="difficulty"
                                            required>
                                        <option value="beginner" {{ old('difficulty') == 'beginner' ? 'selected' : '' }}>Beginner</option>
                                        <option value="intermediate" {{ old('difficulty') == 'intermediate' ? 'selected' : '' }}>Intermediate</option>
                                        <option value="advanced" {{ old('difficulty') == 'advanced' ? 'selected' : '' }}>Advanced</option>
                                        <option value="expert" {{ old('difficulty') == 'expert' ? 'selected' : '' }}>Expert</option>
                                    </select>
                                    @error('difficulty')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="time_limit" class="form-label">Time Limit (minutes) <span class="text-danger">*</span></label>
                                    <input type="number" 
                                           class="form-control @error('time_limit') is-invalid @enderror" 
                                           id="time_limit" 
                                           name="time_limit" 
                                           value="{{ old('time_limit', 30) }}"
                                           min="1"
                                           max="300"
                                           required>
                                    @error('time_limit')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="passing_score" class="form-label">Passing Score (%) <span class="text-danger">*</span></label>
                                    <input type="number" 
                                           class="form-control @error('passing_score') is-invalid @enderror" 
                                           id="passing_score" 
                                           name="passing_score" 
                                           value="{{ old('passing_score', 70) }}"
                                           min="0"
                                           max="100"
                                           required>
                                    @error('passing_score')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="max_attempts" class="form-label">Max Attempts</label>
                                    <input type="number" 
                                           class="form-control @error('max_attempts') is-invalid @enderror" 
                                           id="max_attempts" 
                                           name="max_attempts" 
                                           value="{{ old('max_attempts', 0) }}"
                                           min="0">
                                    @error('max_attempts')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">0 = Unlimited attempts</small>
                                </div>

                                <div class="mb-3">
                                    <label for="points_per_question" class="form-label">Points per Question</label>
                                    <input type="number" 
                                           class="form-control @error('points_per_question') is-invalid @enderror" 
                                           id="points_per_question" 
                                           name="points_per_question" 
                                           value="{{ old('points_per_question', 10) }}"
                                           min="1"
                                           max="100">
                                    @error('points_per_question')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" 
                                               class="form-check-input" 
                                               id="shuffle_questions" 
                                               name="shuffle_questions" 
                                               value="1"
                                               {{ old('shuffle_questions') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="shuffle_questions">
                                            Shuffle Questions
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" 
                                               class="form-check-input" 
                                               id="show_answers" 
                                               name="show_answers" 
                                               value="1"
                                               {{ old('show_answers', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="show_answers">
                                            Show Answers After Quiz
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>
                        Create Quiz
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>
                        Reset
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection