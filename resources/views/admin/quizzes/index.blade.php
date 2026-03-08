@extends('layouts.admin')

@section('title', 'Quiz Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Quiz Management</h2>
        <a href="{{ route('admin.quizzes.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>
            Create New Quiz
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Difficulty</th>
                            <th>Questions</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($quizzes as $quiz)
                            <tr>
                                <td>{{ $quiz->id }}</td>
                                <td>
                                    <strong>{{ $quiz->title }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $quiz->slug }}</small>
                                </td>
                                <td>{{ $quiz->category->name ?? 'Uncategorized' }}</td>
                                <td>
                                    <span class="{{ getDifficultyBadge($quiz->difficulty) }}">
                                        {{ ucfirst($quiz->difficulty) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        {{ $quiz->questions_count ?? $quiz->questions->count() }}
                                    </span>
                                </td>
                                <td>
                                    @if($quiz->is_published)
                                        <span class="badge bg-success">Published</span>
                                        <br>
                                        <small>{{ $quiz->published_at->diffForHumans() }}</small>
                                    @else
                                        <span class="badge bg-warning text-dark">Draft</span>
                                    @endif
                                </td>
                                <td>{{ $quiz->created_at->format('Y-m-d') }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.quizzes.edit', $quiz->id) }}" 
                                           class="btn btn-sm btn-outline-primary"
                                           title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        
                                        <a href="{{ route('admin.quizzes.questions.index', $quiz->id) }}" 
                                           class="btn btn-sm btn-outline-info"
                                           title="Manage Questions">
                                            <i class="bi bi-question-circle"></i>
                                        </a>
                                        
                                        @if($quiz->is_published)
                                            <form action="{{ route('admin.quizzes.unpublish', $quiz->id) }}" 
                                                  method="POST" 
                                                  class="d-inline">
                                                @csrf
                                                <button type="submit" 
                                                        class="btn btn-sm btn-outline-warning"
                                                        title="Unpublish"
                                                        onclick="return confirm('Unpublish this quiz?')">
                                                    <i class="bi bi-eye-slash"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.quizzes.publish', $quiz->id) }}" 
                                                  method="POST" 
                                                  class="d-inline">
                                                @csrf
                                                <button type="submit" 
                                                        class="btn btn-sm btn-outline-success"
                                                        title="Publish"
                                                        onclick="return confirm('Publish this quiz?')">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </form>
                                        @endif
                                        
                                        <form action="{{ route('admin.quizzes.destroy', $quiz->id) }}" 
                                              method="POST" 
                                              class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="Delete"
                                                    onclick="return confirm('Are you sure? This will delete all questions and attempts.')">
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
            
            <div class="mt-4">
                {{ $quizzes->links() }}
            </div>
        </div>
    </div>
</div>
@endsection