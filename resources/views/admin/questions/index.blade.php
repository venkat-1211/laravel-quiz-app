@extends('layouts.admin')

@section('title', 'Manage Questions')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Manage Questions</h2>
            <p class="text-muted mb-0">Quiz: <strong>{{ $quiz->title }}</strong></p>
        </div>
        <div>
            <a href="{{ route('admin.quizzes.questions.create', $quiz->id) }}" class="btn btn-primary me-2">
                <i class="bi bi-plus-circle me-2"></i>
                Add New Question
            </a>
            <a href="{{ route('admin.quizzes.edit', $quiz->id) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>
                Back to Quiz
            </a>
        </div>
    </div>

    <!-- Quiz Info Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="text-white-50">Total Questions</h6>
                    <h3 class="mb-0">{{ $questions->total() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="text-white-50">Easy Questions</h6>
                    <h3 class="mb-0">{{ $quiz->questions->where('difficulty', 'easy')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="text-white-50">Medium Questions</h6>
                    <h3 class="mb-0">{{ $quiz->questions->where('difficulty', 'medium')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6 class="text-white-50">Hard Questions</h6>
                    <h3 class="mb-0">{{ $quiz->questions->where('difficulty', 'hard')->count() }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.quizzes.questions.index', $quiz->id) }}" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search questions..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="difficulty" class="form-select">
                        <option value="">All Difficulties</option>
                        <option value="easy" {{ request('difficulty') == 'easy' ? 'selected' : '' }}>Easy</option>
                        <option value="medium" {{ request('difficulty') == 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="hard" {{ request('difficulty') == 'hard' ? 'selected' : '' }}>Hard</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="has_explanation" class="form-select">
                        <option value="">All Questions</option>
                        <option value="yes" {{ request('has_explanation') == 'yes' ? 'selected' : '' }}>With Explanation</option>
                        <option value="no" {{ request('has_explanation') == 'no' ? 'selected' : '' }}>Without Explanation</option>
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

    <!-- Bulk Actions -->
    <div class="card mb-4">
        <div class="card-body py-2">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="selectAll">
                        <label class="form-check-label" for="selectAll">
                            Select All Questions
                        </label>
                    </div>
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="bulkDeleteBtn" disabled onclick="bulkDelete()">
                        <i class="bi bi-trash me-2"></i>Delete Selected
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="bulkDuplicateBtn" disabled onclick="bulkDuplicate()">
                        <i class="bi bi-files me-2"></i>Duplicate Selected
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#bulkUploadModal">
                        <i class="bi bi-upload me-2"></i>Bulk Upload CSV
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Questions Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th width="40">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="selectAllHeader">
                                </div>
                            </th>
                            <th width="50">#</th>
                            <th>Question</th>
                            <th width="100">Difficulty</th>
                            <th width="80">Points</th>
                            <th width="100">Options</th>
                            <th width="100">Explanation</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="sortable-questions">
                        @forelse($questions as $index => $question)
                            <tr data-id="{{ $question->id }}" data-order="{{ $question->order }}">
                                <td>
                                    <div class="form-check">
                                        <input class="form-check-input question-checkbox" type="checkbox" value="{{ $question->id }}">
                                    </div>
                                </td>
                                <td>
                                    <span class="drag-handle" style="cursor: move;">
                                        <i class="bi bi-grip-vertical text-muted"></i>
                                    </span>
                                    {{ $question->order }}
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($question->image_url)
                                            <img src="{{ $question->image_url }}" class="rounded me-2" width="40" height="40" style="object-fit: cover;">
                                        @endif
                                        <div>
                                            <strong>{{ \Illuminate\Support\Str::limit($question->question_text, 60) }}</strong>
                                            @if($question->video_url)
                                                <i class="bi bi-camera-video text-info ms-1" title="Has video"></i>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge {{ $question->difficulty == 'easy' ? 'bg-success' : ($question->difficulty == 'medium' ? 'bg-warning text-dark' : 'bg-danger') }}">
                                        {{ ucfirst($question->difficulty) }}
                                    </span>
                                </td>
                                <td class="text-center">{{ $question->points }}</td>
                                <td>
                                    <span class="badge bg-info">{{ count($question->options) }}</span>
                                </td>
                                <td>
                                    @if($question->explanation)
                                        <i class="bi bi-check-circle-fill text-success" title="Has explanation"></i>
                                    @else
                                        <i class="bi bi-x-circle-fill text-danger" title="No explanation"></i>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.quizzes.questions.edit', [$quiz->id, $question->id]) }}" 
                                           class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-info" 
                                                title="Preview"
                                                onclick="previewQuestion({{ $question->id }})">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-success" 
                                                title="Duplicate"
                                                onclick="duplicateQuestion({{ $question->id }})">
                                            <i class="bi bi-files"></i>
                                        </button>
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
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="bi bi-question-circle fs-1 text-muted d-block mb-3"></i>
                                    <h5>No questions found</h5>
                                    <p class="text-muted mb-3">Get started by adding your first question.</p>
                                    <a href="{{ route('admin.quizzes.questions.create', $quiz->id) }}" class="btn btn-primary">
                                        <i class="bi bi-plus-circle me-2"></i>
                                        Add First Question
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    Showing {{ $questions->firstItem() ?? 0 }} to {{ $questions->lastItem() ?? 0 }} of {{ $questions->total() }} questions
                </div>
                <div>
                    {{ $questions->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Question Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Preview Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
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
                        <a href="#" class="alert-link" onclick="downloadSampleCSV()">Download sample CSV template</a>
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
        display: inline-block;
        padding: 0 5px;
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
    .question-checkbox {
        cursor: pointer;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    // Initialize sortable for question reordering
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
                        toastr.success('Question order updated successfully');
                        // Update order numbers in display
                        rows.forEach((row, index) => {
                            const orderCell = row.querySelector('td:nth-child(2)');
                            if (orderCell) {
                                orderCell.innerHTML = `<span class="drag-handle" style="cursor: move;"><i class="bi bi-grip-vertical text-muted"></i></span> ${index + 1}`;
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error updating order:', error);
                    toastr.error('Failed to update question order');
                });
            }
        });
    }

    // Select All functionality
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.question-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateBulkButtons();
    });

    document.getElementById('selectAllHeader').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.question-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
        document.getElementById('selectAll').checked = this.checked;
        updateBulkButtons();
    });

    document.querySelectorAll('.question-checkbox').forEach(cb => {
        cb.addEventListener('change', updateBulkButtons);
    });

    function updateBulkButtons() {
        const selected = document.querySelectorAll('.question-checkbox:checked').length;
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        const bulkDuplicateBtn = document.getElementById('bulkDuplicateBtn');
        
        if (selected > 0) {
            bulkDeleteBtn.disabled = false;
            bulkDuplicateBtn.disabled = false;
        } else {
            bulkDeleteBtn.disabled = true;
            bulkDuplicateBtn.disabled = true;
        }
    }

    function bulkDelete() {
        const selected = Array.from(document.querySelectorAll('.question-checkbox:checked')).map(cb => cb.value);
        if (selected.length === 0) return;
        
        if (confirm(`Are you sure you want to delete ${selected.length} question(s)?`)) {
            fetch('{{ route("admin.quizzes.questions.bulk-delete", $quiz->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ question_ids: selected })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    toastr.success(`${data.deleted} question(s) deleted successfully`);
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    toastr.error('Failed to delete questions');
                }
            });
        }
    }

    function bulkDuplicate() {
        const selected = Array.from(document.querySelectorAll('.question-checkbox:checked')).map(cb => cb.value);
        if (selected.length === 0) return;
        
        fetch('{{ route("admin.quizzes.questions.bulk-duplicate", $quiz->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ question_ids: selected })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success(`${data.duplicated} question(s) duplicated successfully`);
                setTimeout(() => window.location.reload(), 1500);
            } else {
                toastr.error('Failed to duplicate questions');
            }
        });
    }

    function previewQuestion(questionId) {
        const modal = new bootstrap.Modal(document.getElementById('previewModal'));
        const previewContent = document.getElementById('previewContent');
        
        previewContent.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        modal.show();
        
        fetch(`/admin/questions/${questionId}/preview`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(html => {
                previewContent.innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
                previewContent.innerHTML = '<div class="alert alert-danger">Failed to load question preview. Please try again.</div>';
            });
    }

    function duplicateQuestion(questionId) {
        if (confirm('Are you sure you want to duplicate this question?')) {
            fetch(`/admin/questions/${questionId}/duplicate`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    toastr.success('Question duplicated successfully');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    toastr.error('Failed to duplicate question');
                }
            });
        }
    }

    function downloadSampleCSV() {
        const csv = 'question_text,option_a,option_b,option_c,option_d,correct_answer,explanation,difficulty,points\n';
        const sample = csv + '"What is the capital of France?",Paris,London,Berlin,Madrid,A,"Paris is the capital of France.",medium,10';
        
        const blob = new Blob([sample], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'sample_questions.csv';
        a.click();
    }

    // Toastr configuration
    toastr.options = {
        positionClass: 'toast-top-right',
        progressBar: true,
        timeOut: 3000
    };
</script>
@endpush