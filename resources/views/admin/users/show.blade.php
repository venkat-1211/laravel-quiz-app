@extends('layouts.admin')

@section('title', 'User Details - ' . $user->name)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>User Details</h2>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>
            Back to Users
        </a>
    </div>

    <div class="row">
        <!-- User Info Card -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    @if($user->avatar)
                        <img src="{{ $user->avatar }}" class="rounded-circle mb-3" width="120" height="120" alt="{{ $user->name }}">
                    @else
                        <div class="avatar-circle-large bg-primary text-white mx-auto mb-3">
                            {{ getInitials($user->name) }}
                        </div>
                    @endif
                    
                    <h4>{{ $user->name }}</h4>
                    <p class="text-muted">{{ $user->email }}</p>
                    
                    <div class="mb-3">
                        @if($user->hasRole('admin'))
                            <span class="badge bg-danger">Administrator</span>
                        @else
                            <span class="badge bg-info">Regular User</span>
                        @endif
                        
                        @if($user->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </div>
                    
                    <hr>
                    
                    <div class="text-start">
                        <p><strong>Joined:</strong> {{ $user->created_at->format('F d, Y') }}</p>
                        <p><strong>Last Login:</strong> {{ $user->last_login_at ? $user->last_login_at->format('F d, Y H:i') : 'Never' }}</p>
                        <p><strong>Last IP:</strong> {{ $user->last_login_ip ?? 'N/A' }}</p>
                        <p><strong>Email Verified:</strong> 
                            @if($user->email_verified_at)
                                <span class="text-success">Yes ({{ $user->email_verified_at->format('Y-m-d') }})</span>
                            @else
                                <span class="text-danger">No</span>
                            @endif
                        </p>
                        <p><strong>Social Login:</strong> 
                            @if($user->social_type)
                                <span class="badge bg-info">{{ ucfirst($user->social_type) }}</span>
                            @else
                                <span class="text-muted">Standard</span>
                            @endif
                        </p>
                    </div>
                    
                    <hr>
                    
                    <div class="row text-center">
                        <div class="col-4">
                            <h5>{{ $stats['total_attempts'] }}</h5>
                            <small class="text-muted">Attempts</small>
                        </div>
                        <div class="col-4">
                            <h5>{{ $stats['completed_quizzes'] }}</h5>
                            <small class="text-muted">Completed</small>
                        </div>
                        <div class="col-4">
                            <h5>{{ $stats['average_score'] }}%</h5>
                            <small class="text-muted">Avg Score</small>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-grid gap-2">
                        <form action="{{ route('admin.users.toggle-status', $user->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn {{ $user->is_active ? 'btn-warning' : 'btn-success' }} w-100 mb-2">
                                <i class="bi {{ $user->is_active ? 'bi-pause-circle' : 'bi-play-circle' }} me-2"></i>
                                {{ $user->is_active ? 'Deactivate User' : 'Activate User' }}
                            </button>
                        </form>
                        
                        <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                <i class="bi bi-trash me-2"></i>
                                Delete User
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Performance Charts -->
        <div class="col-md-8 mb-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Performance Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="performanceChart" height="100"></canvas>
                </div>
            </div>
            
            <!-- Recent Attempts -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Quiz Attempts</h5>
                </div>
                <div class="card-body">
                    @if($recentAttempts->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Quiz</th>
                                        <th>Score</th>
                                        <th>Correct</th>
                                        <th>Time Taken</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentAttempts as $attempt)
                                        <tr>
                                            <td>{{ $attempt->quiz->title }}</td>
                                            <td>
                                                <span class="badge {{ $attempt->percentage_score >= 70 ? 'bg-success' : 'bg-danger' }}">
                                                    {{ $attempt->percentage_score }}%
                                                </span>
                                            </td>
                                            <td>{{ $attempt->correct_answers }}/{{ $attempt->total_questions }}</td>
                                            <td>{{ floor($attempt->time_taken / 60) }}:{{ str_pad($attempt->time_taken % 60, 2, '0', STR_PAD_LEFT) }}</td>
                                            <td>{{ $attempt->created_at->format('Y-m-d H:i') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted text-center py-3">No quiz attempts yet</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .avatar-circle-large {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 48px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('performanceChart').getContext('2d');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($performanceData['labels']) !!},
                datasets: [{
                    label: 'Score (%)',
                    data: {!! json_encode($performanceData['scores']) !!},
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    });
</script>
@endpush