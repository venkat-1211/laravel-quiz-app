@extends('layouts.app')

@section('title', 'Quiz History - Quiz App')

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h2 mb-0">
                    <i class="bi bi-clock-history me-2 text-primary"></i>
                    Quiz History
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
            <p class="text-muted mt-2">View all your quiz attempts and performance history</p>
        </div>
    </div>

    <!-- Stats Summary -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-primary-soft me-3">
                            <i class="bi bi-pencil-square text-primary"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Total Attempts</h6>
                            <h3 class="mb-0">{{ $attempts->total() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-success-soft me-3">
                            <i class="bi bi-check-circle text-success"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Completed</h6>
                            <h3 class="mb-0">{{ auth()->user()->attempts()->where('status', 'completed')->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-info-soft me-3">
                            <i class="bi bi-star text-info"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Avg Score</h6>
                            <h3 class="mb-0">{{ round(auth()->user()->attempts()->where('status', 'completed')->avg('percentage_score') ?? 0, 1) }}%</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-warning-soft me-3">
                            <i class="bi bi-trophy text-warning"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Total Points</h6>
                            <h3 class="mb-0">{{ auth()->user()->leaderboard->total_points ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('profile.history') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="quiz_id" class="form-label">Quiz</label>
                    <select class="form-select" id="quiz_id" name="quiz_id">
                        <option value="">All Quizzes</option>
                        @foreach($quizzes ?? [] as $quiz)
                            <option value="{{ $quiz->id }}" {{ request('quiz_id') == $quiz->id ? 'selected' : '' }}>
                                {{ $quiz->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="timed_out" {{ request('status') == 'timed_out' ? 'selected' : '' }}>Timed Out</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
                </div>
                
                <div class="col-md-2">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
                </div>
                
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search me-2"></i>Filter
                    </button>
                    <a href="{{ route('profile.history') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- History Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($attempts->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>Quiz</th>
                                <th>Category</th>
                                <th>Score</th>
                                <th>Correct</th>
                                <th>Time Taken</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attempts as $index => $attempt)
                                <tr>
                                    <td>{{ $attempts->firstItem() + $index }}</td>
                                    <td>
                                        <strong>{{ $attempt->quiz->title }}</strong>
                                    </td>
                                    <td>
                                        @if($attempt->quiz->category)
                                            <span class="badge bg-light text-dark">
                                                <i class="bi bi-folder me-1"></i>{{ $attempt->quiz->category->name }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $attempt->percentage_score >= 70 ? 'bg-success' : ($attempt->percentage_score >= 40 ? 'bg-warning text-dark' : 'bg-danger') }} rounded-pill px-3 py-2">
                                            {{ $attempt->percentage_score }}%
                                        </span>
                                    </td>
                                    <td>{{ $attempt->correct_answers }}/{{ $attempt->total_questions }}</td>
                                    <td>
                                        <i class="bi bi-clock me-1"></i>
                                        {{ floor($attempt->time_taken / 60) }}:{{ str_pad($attempt->time_taken % 60, 2, '0', STR_PAD_LEFT) }}
                                    </td>
                                    <td>
                                        @if($attempt->status == 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @elseif($attempt->status == 'in_progress')
                                            <span class="badge bg-warning text-dark">In Progress</span>
                                        @else
                                            <span class="badge bg-secondary">Timed Out</span>
                                        @endif
                                    </td>
                                    <td>{{ $attempt->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('attempt.results', $attempt->id) }}" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="View Results">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            @if($attempt->status == 'completed')
                                                <a href="{{ route('attempt.review', $attempt->id) }}" 
                                                   class="btn btn-sm btn-outline-info" 
                                                   title="Review Answers">
                                                    <i class="bi bi-journal-check"></i>
                                                </a>
                                            @endif
                                            @if($attempt->status == 'completed' && $attempt->percentage_score >= $attempt->quiz->passing_score)
                                                <a href="#" 
                                                   class="btn btn-sm btn-outline-success" 
                                                   title="Download Certificate"
                                                   onclick="downloadCertificate({{ $attempt->id }})">
                                                    <i class="bi bi-award"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="px-3 py-3 border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            Showing {{ $attempts->firstItem() ?? 0 }} to {{ $attempts->lastItem() ?? 0 }} of {{ $attempts->total() }} entries
                        </div>
                        <div>
                            {{ $attempts->withQueryString()->links() }}
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                    </div>
                    <h5>No quiz attempts found</h5>
                    <p class="text-muted mb-4">You haven't taken any quizzes yet. Start your first quiz now!</p>
                    <a href="{{ route('quizzes.index') }}" class="btn btn-primary">
                        <i class="bi bi-play-circle me-2"></i>
                        Browse Quizzes
                    </a>
                </div>
            @endif
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
    
    .bg-info-soft {
        background-color: rgba(23, 162, 184, 0.1);
    }
    
    .bg-warning-soft {
        background-color: rgba(255, 193, 7, 0.1);
    }
    
    .table th {
        font-weight: 600;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6c757d;
        border-bottom-width: 1px;
    }
    
    .table td {
        vertical-align: middle;
        padding: 1rem 0.75rem;
    }
    
    .btn-group .btn {
        padding: 0.4rem 0.6rem;
    }
    
    .btn-group .btn i {
        font-size: 1rem;
    }
    
    .badge {
        font-weight: 500;
        padding: 0.5rem 1rem;
    }
    
    .form-select, .form-control {
        border-radius: 8px;
        border: 1px solid #e0e0e0;
    }
    
    .form-select:focus, .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
</style>
@endpush

@push('scripts')
<script>
    function downloadCertificate(attemptId) {
        // Show loading toast
        toastr.info('Generating certificate...');
        
        fetch(`/attempt/${attemptId}/certificate`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `certificate-${attemptId}.pdf`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            toastr.success('Certificate downloaded successfully!');
        })
        .catch(error => {
            console.error('Error:', error);
            toastr.error('Failed to generate certificate');
        });
    }

    // Auto-submit form when filters change
    document.querySelectorAll('select[name="quiz_id"], select[name="status"]').forEach(select => {
        select.addEventListener('change', function() {
            this.form.submit();
        });
    });

    // Date validation
    document.getElementById('date_from').addEventListener('change', function() {
        const dateTo = document.getElementById('date_to');
        if (dateTo.value && this.value > dateTo.value) {
            dateTo.value = this.value;
        }
        dateTo.min = this.value;
    });

    document.getElementById('date_to').addEventListener('change', function() {
        const dateFrom = document.getElementById('date_from');
        if (dateFrom.value && this.value < dateFrom.value) {
            dateFrom.value = this.value;
        }
    });

    // Toastr configuration
    toastr.options = {
        positionClass: 'toast-top-right',
        progressBar: true,
        timeOut: 3000,
        closeButton: true
    };
</script>
@endpush