@extends('layouts.admin')

@section('title', 'Edit Quiz - ' . $quiz->title)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Edit Quiz: {{ $quiz->title }}</h2>
        <a href="{{ route('admin.quizzes.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>
            Back to Quizzes
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.quizzes.update', $quiz->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="title" class="form-label">Quiz Title <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('title') is-invalid @enderror" 
                                   id="title" 
                                   name="title" 
                                   value="{{ old('title', $quiz->title) }}"
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
                                      required>{{ old('description', $quiz->description) }}</textarea>
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
                            <small class="text-muted">Leave empty to keep current image. Recommended size: 1200x600px (Max 2MB)</small>
                            
                            @if($quiz->featured_image)
                                <div class="mt-2">
                                    <img src="{{ asset('storage/' . $quiz->featured_image) }}" alt="Current featured image" class="img-thumbnail" style="max-height: 100px;">
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-4">
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
                                            <option value="{{ $category->id }}" {{ old('category_id', $quiz->category_id) == $category->id ? 'selected' : '' }}>
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
                                        <option value="beginner" {{ old('difficulty', $quiz->difficulty) == 'beginner' ? 'selected' : '' }}>Beginner</option>
                                        <option value="intermediate" {{ old('difficulty', $quiz->difficulty) == 'intermediate' ? 'selected' : '' }}>Intermediate</option>
                                        <option value="advanced" {{ old('difficulty', $quiz->difficulty) == 'advanced' ? 'selected' : '' }}>Advanced</option>
                                        <option value="expert" {{ old('difficulty', $quiz->difficulty) == 'expert' ? 'selected' : '' }}>Expert</option>
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
                                           value="{{ old('time_limit', $quiz->time_limit) }}"
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
                                           value="{{ old('passing_score', $quiz->passing_score) }}"
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
                                           value="{{ old('max_attempts', $quiz->max_attempts) }}"
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
                                           value="{{ old('points_per_question', $quiz->points_per_question) }}"
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
                                               {{ old('shuffle_questions', $quiz->shuffle_questions) ? 'checked' : '' }}>
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
                                               {{ old('show_answers', $quiz->show_answers) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="show_answers">
                                            Show Answers After Quiz
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" 
                                               class="form-check-input" 
                                               id="is_published" 
                                               name="is_published" 
                                               value="1"
                                               {{ old('is_published', $quiz->is_published) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_published">
                                            Published
                                        </label>
                                    </div>
                                    <small class="text-muted">Uncheck to save as draft</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-between">
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>
                            Update Quiz
                        </button>
                        <a href="{{ route('admin.quizzes.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-2"></i>
                            Cancel
                        </a>
                    </div>
                    
                    @if($quiz->is_published)
                        <form action="{{ route('admin.quizzes.unpublish', $quiz->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-warning" onclick="return confirm('Unpublish this quiz?')">
                                <i class="bi bi-eye-slash me-2"></i>
                                Unpublish
                            </button>
                        </form>
                    @else
                        <form action="{{ route('admin.quizzes.publish', $quiz->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success" onclick="return confirm('Publish this quiz?')">
                                <i class="bi bi-eye me-2"></i>
                                Publish
                            </button>
                        </form>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Questions Section -->
    @if($quiz->id)
    <div class="card mt-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-question-circle me-2 text-primary"></i>
                Questions ({{ $quiz->questions->count() }})
            </h5>
            <a href="{{ route('admin.quizzes.questions.create', $quiz->id) }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle me-2"></i>
                Add Question
            </a>
        </div>
        <div class="card-body">
            @if($quiz->questions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Question</th>
                                <th>Difficulty</th>
                                <th>Points</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="sortable-questions">
                            @foreach($quiz->questions->sortBy('order') as $index => $question)
                                <tr data-id="{{ $question->id }}" data-order="{{ $question->order }}">
                                    <td class="drag-handle" style="cursor: move;">
                                        <i class="bi bi-grip-vertical"></i> {{ $index + 1 }}
                                    </td>
                                    <td>{{ \Illuminate\Support\Str::limit($question->question_text, 50) }}</td>
                                    <td>
                                        <span class="badge {{ $question->difficulty == 'easy' ? 'bg-success' : ($question->difficulty == 'medium' ? 'bg-warning' : 'bg-danger') }}">
                                            {{ ucfirst($question->difficulty) }}
                                        </span>
                                    </td>
                                    <td>{{ $question->points }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.quizzes.questions.edit', [$quiz->id, $question->id]) }}" 
                                               class="btn btn-sm btn-outline-primary"
                                               title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('admin.quizzes.questions.destroy', [$quiz->id, $question->id]) }}" 
                                                  method="POST" 
                                                  class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="btn btn-sm btn-outline-danger"
                                                        title="Delete"
                                                        onclick="return confirm('Are you sure you want to delete this question?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    <a href="{{ route('admin.quizzes.questions.index', $quiz->id) }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-list me-2"></i>
                        Manage All Questions
                    </a>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#bulkUploadModal">
                        <i class="bi bi-upload me-2"></i>
                        Bulk Upload CSV
                    </button>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No questions added yet.</p>
                    <a href="{{ route('admin.quizzes.questions.create', $quiz->id) }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>
                        Add First Question
                    </a>
                </div>
            @endif
        </div>
    </div>
    @endif
</div>

<!-- Bulk Upload Modal -->
<div class="modal fade" id="bulkUploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.quizzes.questions.bulk-upload', $quiz->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Bulk Upload Questions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="csv_file" class="form-label">CSV File</label>
                        <input type="file" class="form-control" id="csv_file" name="file" accept=".csv" required>
                        <small class="text-muted">
                            CSV format: question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, difficulty, points
                        </small>
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <a href="#" class="alert-link">Download sample CSV template</a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .drag-handle {
        cursor: move;
    }
    .drag-handle i {
        color: #6c757d;
    }
    .table tbody tr.dragging {
        opacity: 0.5;
        background: #f8f9fa;
    }
    .table tbody tr.drag-over {
        border: 2px dashed #667eea;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const questionsTable = document.getElementById('sortable-questions');
        if (questionsTable) {
            new Sortable(questionsTable, {
                animation: 150,
                handle: '.drag-handle',
                ghostClass: 'dragging',
                dragClass: 'drag-over',
                onEnd: function(evt) {
                    const order = [];
                    const rows = questionsTable.querySelectorAll('tr');
                    rows.forEach((row, index) => {
                        const questionId = row.dataset.id;
                        if (questionId) {
                            order.push({
                                id: questionId,
                                order: index + 1
                            });
                        }
                    });
                    
                    fetch('{{ route("admin.quizzes.questions.reorder", $quiz->id) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ order: order })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log('Order updated successfully');
                        }
                    });
                }
            });
        }
    });
</script>
@endpush