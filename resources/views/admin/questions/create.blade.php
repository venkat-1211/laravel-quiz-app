@extends('layouts.admin')

@section('title', 'Add Question')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Add New Question</h2>
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
                        <i class="bi bi-question-circle me-2 text-primary"></i>
                        Question Details
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.quizzes.questions.store', $quiz->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <!-- Quiz Info (Hidden) -->
                        <input type="hidden" name="quiz_id" value="{{ $quiz->id }}">

                        <!-- Question Text -->
                        <div class="mb-3">
                            <label for="question_text" class="form-label">Question Text <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('question_text') is-invalid @enderror" 
                                      id="question_text" 
                                      name="question_text" 
                                      rows="3"
                                      required>{{ old('question_text') }}</textarea>
                            @error('question_text')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Options -->
                        <div class="mb-3">
                            <label class="form-label">Answer Options <span class="text-danger">*</span></label>
                            <div class="options-container">
                                @foreach(['A', 'B', 'C', 'D'] as $letter)
                                    <div class="input-group mb-2">
                                        <span class="input-group-text bg-primary text-white fw-bold">{{ $letter }}</span>
                                        <input type="text" 
                                               class="form-control @error('options.' . $letter) is-invalid @enderror" 
                                               name="options[{{ $letter }}]" 
                                               value="{{ old('options.' . $letter) }}"
                                               placeholder="Option {{ $letter }}"
                                               required>
                                        <div class="input-group-text">
                                            <input class="form-check-input mt-0" 
                                                   type="radio" 
                                                   name="correct_answer" 
                                                   value="{{ $letter }}"
                                                   {{ old('correct_answer') == $letter ? 'checked' : '' }}
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
                                      rows="3">{{ old('explanation') }}</textarea>
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
                                       value="{{ old('image_url') }}"
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
                                       value="{{ old('video_url') }}"
                                       placeholder="https://youtube.com/watch?v=...">
                                @error('video_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Image Preview -->
                        <div class="mb-3" id="imagePreview" style="display: none;">
                            <label class="form-label">Image Preview</label>
                            <img src="" class="img-thumbnail" style="max-height: 200px;" alt="Preview">
                        </div>

                        <hr>

                        <!-- Additional Settings -->
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="difficulty" class="form-label">Difficulty</label>
                                <select class="form-select @error('difficulty') is-invalid @enderror" 
                                        id="difficulty" 
                                        name="difficulty">
                                    <option value="easy" {{ old('difficulty') == 'easy' ? 'selected' : '' }}>Easy</option>
                                    <option value="medium" {{ old('difficulty') == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="hard" {{ old('difficulty') == 'hard' ? 'selected' : '' }}>Hard</option>
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
                                       value="{{ old('points', 10) }}"
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
                                       value="{{ old('order', $quiz->questions->count() + 1) }}"
                                       min="1">
                                @error('order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>
                                Save Question
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
                    <p class="small text-muted">{{ $quiz->description }}</p>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Questions:</span>
                        <span class="fw-bold">{{ $quiz->questions->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Difficulty:</span>
                        <span class="badge {{ getDifficultyBadge($quiz->difficulty) }}">{{ ucfirst($quiz->difficulty) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Time Limit:</span>
                        <span>{{ $quiz->formatted_time_limit }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Points per Question:</span>
                        <span>{{ $quiz->points_per_question }}</span>
                    </div>
                </div>
            </div>

            <!-- Tips Card -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-lightbulb me-2 text-warning"></i>
                        Tips
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2 small"></i>
                            Make sure the question is clear and unambiguous
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2 small"></i>
                            Provide 4 options for multiple choice questions
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2 small"></i>
                            Add explanations to help users learn
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2 small"></i>
                            Use images or videos for complex concepts
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill text-success me-2 small"></i>
                            Mark the correct answer using the radio button
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Image URL preview
    document.getElementById('image_url').addEventListener('input', function() {
        const url = this.value;
        const preview = document.getElementById('imagePreview');
        const img = preview.querySelector('img');
        
        if (url) {
            img.src = url;
            preview.style.display = 'block';
            
            img.onerror = function() {
                preview.style.display = 'none';
            };
        } else {
            preview.style.display = 'none';
        }
    });

    // Auto-validate correct answer selection
    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.input-group-text').forEach((el, index) => {
                if (index > 0) { // Skip the first one (A)
                    el.classList.remove('bg-success');
                }
            });
            
            if (this.checked) {
                const parent = this.closest('.input-group');
                const letterSpan = parent.querySelector('.input-group-text:first-child');
                letterSpan.classList.add('bg-success');
                letterSpan.classList.remove('bg-primary');
            }
        });
    });

    // Show success styling for pre-selected correct answer
    window.addEventListener('load', function() {
        const checkedRadio = document.querySelector('input[type="radio"]:checked');
        if (checkedRadio) {
            const parent = checkedRadio.closest('.input-group');
            const letterSpan = parent.querySelector('.input-group-text:first-child');
            letterSpan.classList.add('bg-success');
            letterSpan.classList.remove('bg-primary');
        }
    });
</script>
@endpush