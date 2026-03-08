@extends('layouts.admin')

@section('title', 'Edit Question')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Edit Question</h2>
            <p class="text-muted mb-0">Quiz: <strong>{{ $quiz->title }}</strong></p>
        </div>
        <a href="{{ route('admin.quizzes.questions.index', $quiz->id) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>
            Back to Questions
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-pencil-square me-2 text-primary"></i>
                        Edit Question
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.quizzes.questions.update', [$quiz->id, $question->id]) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <!-- Quiz Info (Hidden) -->
                        <input type="hidden" name="quiz_id" value="{{ $quiz->id }}">

                        <!-- Question Text -->
                        <div class="mb-3">
                            <label for="question_text" class="form-label">Question Text <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('question_text') is-invalid @enderror" 
                                      id="question_text" 
                                      name="question_text" 
                                      rows="3"
                                      required>{{ old('question_text', $question->question_text) }}</textarea>
                            @error('question_text')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Options -->
                        <div class="mb-3">
                            <label class="form-label">Answer Options <span class="text-danger">*</span></label>
                            <div class="options-container">
                                @foreach(['A', 'B', 'C', 'D'] as $letter)
                                    @php
                                        $optionValue = $question->options[$letter] ?? '';
                                    @endphp
                                    <div class="input-group mb-2">
                                        <span class="input-group-text bg-primary text-white fw-bold option-letter {{ $question->correct_answer == $letter ? 'bg-success' : '' }}">{{ $letter }}</span>
                                        <input type="text" 
                                               class="form-control @error('options.' . $letter) is-invalid @enderror" 
                                               name="options[{{ $letter }}]" 
                                               value="{{ old('options.' . $letter, $optionValue) }}"
                                               placeholder="Option {{ $letter }}"
                                               required>
                                        <div class="input-group-text">
                                            <input class="form-check-input mt-0" 
                                                   type="radio" 
                                                   name="correct_answer" 
                                                   value="{{ $letter }}"
                                                   {{ old('correct_answer', $question->correct_answer) == $letter ? 'checked' : '' }}
                                                   required>
                                        </div>
                                    </div>
                                    @error('options.' . $letter)
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                @endforeach
                            </div>
                            <small class="text-muted">Select the radio button next to the correct answer</small>
                            @error('correct_answer')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Explanation -->
                        <div class="mb-3">
                            <label for="explanation" class="form-label">Explanation</label>
                            <textarea class="form-control @error('explanation') is-invalid @enderror" 
                                      id="explanation" 
                                      name="explanation" 
                                      rows="3">{{ old('explanation', $question->explanation) }}</textarea>
                            @error('explanation')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Provide an explanation for the correct answer (optional)</small>
                        </div>

                        <!-- Media Upload -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="image_url" class="form-label">Image URL</label>
                                <input type="url" 
                                       class="form-control @error('image_url') is-invalid @enderror" 
                                       id="image_url" 
                                       name="image_url" 
                                       value="{{ old('image_url', $question->image_url) }}"
                                       placeholder="https://example.com/image.jpg">
                                @error('image_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="video_url" class="form-label">Video URL</label>
                                <input type="url" 
                                       class="form-control @error('video_url') is-invalid @enderror" 
                                       id="video_url" 
                                       name="video_url" 
                                       value="{{ old('video_url', $question->video_url) }}"
                                       placeholder="https://youtube.com/watch?v=...">
                                @error('video_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Image Preview -->
                        @if($question->image_url)
                            <div class="mb-3" id="imagePreview">
                                <label class="form-label">Current Image</label>
                                <div>
                                    <img src="{{ $question->image_url }}" class="img-thumbnail" style="max-height: 200px;" alt="Current image">
                                </div>
                            </div>
                        @endif

                        <div class="mb-3" id="newImagePreview" style="display: none;">
                            <label class="form-label">New Image Preview</label>
                            <div>
                                <img src="" class="img-thumbnail" style="max-height: 200px;" alt="New image preview">
                            </div>
                        </div>

                        <hr>

                        <!-- Additional Settings -->
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="difficulty" class="form-label">Difficulty</label>
                                <select class="form-select @error('difficulty') is-invalid @enderror" 
                                        id="difficulty" 
                                        name="difficulty">
                                    <option value="easy" {{ old('difficulty', $question->difficulty) == 'easy' ? 'selected' : '' }}>Easy</option>
                                    <option value="medium" {{ old('difficulty', $question->difficulty) == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="hard" {{ old('difficulty', $question->difficulty) == 'hard' ? 'selected' : '' }}>Hard</option>
                                </select>
                                @error('difficulty')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="points" class="form-label">Points</label>
                                <input type="number" 
                                       class="form-control @error('points') is-invalid @enderror" 
                                       id="points" 
                                       name="points" 
                                       value="{{ old('points', $question->points) }}"
                                       min="1"
                                       max="100">
                                @error('points')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="order" class="form-label">Display Order</label>
                                <input type="number" 
                                       class="form-control @error('order') is-invalid @enderror" 
                                       id="order" 
                                       name="order" 
                                       value="{{ old('order', $question->order) }}"
                                       min="1">
                                @error('order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>
                                Update Question
                            </button>
                            <a href="{{ route('admin.quizzes.questions.index', $quiz->id) }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-2"></i>
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Quiz Info Card -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-info-circle me-2 text-primary"></i>
                        Quiz Information
                    </h5>
                </div>
                <div class="card-body">
                    <h6>{{ $quiz->title }}</h6>
                    <p class="small text-muted">{{ Str::limit($quiz->description, 100) }}</p>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Questions:</span>
                        <span class="fw-bold">{{ $quiz->questions->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>This Question #:</span>
                        <span class="fw-bold">{{ $question->order }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Difficulty:</span>
                        <span class="badge {{ $quiz->difficulty == 'beginner' ? 'bg-success' : ($quiz->difficulty == 'intermediate' ? 'bg-info' : ($quiz->difficulty == 'advanced' ? 'bg-warning' : 'bg-danger')) }}">
                            {{ ucfirst($quiz->difficulty) }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Time Limit:</span>
                        <span>{{ floor($quiz->time_limit / 60) }}:{{ str_pad($quiz->time_limit % 60, 2, '0', STR_PAD_LEFT) }} hours</span>
                    </div>
                </div>
            </div>

            <!-- Question Stats Card -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-bar-chart me-2 text-primary"></i>
                        Question Statistics
                    </h5>
                </div>
                <div class="card-body">
                    @php
                        $stats = app(\App\Services\Interfaces\QuestionServiceInterface::class)->getQuestionStats($question->id);
                    @endphp
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Times Used:</span>
                        <span class="fw-bold">{{ $stats['total_attempts'] ?? 0 }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Success Rate:</span>
                        <span class="fw-bold {{ ($stats['success_rate'] ?? 0) >= 70 ? 'text-success' : (($stats['success_rate'] ?? 0) >= 40 ? 'text-warning' : 'text-danger') }}">
                            {{ $stats['success_rate'] ?? 0 }}%
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Avg Time:</span>
                        <span class="fw-bold">{{ floor(($stats['avg_time_spent'] ?? 0) / 60) }}:{{ str_pad(($stats['avg_time_spent'] ?? 0) % 60, 2, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Unique Users:</span>
                        <span class="fw-bold">{{ $stats['unique_users'] ?? 0 }}</span>
                    </div>
                </div>
            </div>

            <!-- Tips Card -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-lightbulb me-2 text-warning"></i>
                        Editing Tips
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2 small"></i>
                            Make sure the question is clear
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2 small"></i>
                            Verify the correct answer is selected
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2 small"></i>
                            Update explanation if needed
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2 small"></i>
                            Check for typos in options
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill text-success me-2 small"></i>
                            Preview after saving
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .option-letter.bg-success {
        background-color: #28a745 !important;
    }
</style>
@endpush

@push('scripts')
<script>
    // Image URL preview for new image
    document.getElementById('image_url').addEventListener('input', function() {
        const url = this.value;
        const currentPreview = document.getElementById('imagePreview');
        const newPreview = document.getElementById('newImagePreview');
        const img = newPreview.querySelector('img');
        
        if (url && url.trim() !== '') {
            img.src = url;
            newPreview.style.display = 'block';
            if (currentPreview) currentPreview.style.display = 'none';
            
            img.onerror = function() {
                newPreview.style.display = 'none';
                if (currentPreview) currentPreview.style.display = 'block';
            };
        } else {
            newPreview.style.display = 'none';
            if (currentPreview) currentPreview.style.display = 'block';
        }
    });

    // Update option letter styling when correct answer changes
    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.option-letter').forEach(el => {
                el.classList.remove('bg-success');
                el.classList.add('bg-primary');
            });
            
            if (this.checked) {
                const parent = this.closest('.input-group');
                const letterSpan = parent.querySelector('.option-letter');
                letterSpan.classList.remove('bg-primary');
                letterSpan.classList.add('bg-success');
            }
        });
    });

    // Confirm before leaving with unsaved changes
    let formChanged = false;
    
    document.querySelectorAll('input, textarea, select').forEach(element => {
        element.addEventListener('change', () => formChanged = true);
    });
    
    // window.addEventListener('beforeunload', function(e) {
    //     if (formChanged) {
    //         e.preventDefault();
    //         e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
    //     }
    // });
    
    document.querySelector('form').addEventListener('submit', function() {
        formChanged = false;
    });
</script>
@endpush