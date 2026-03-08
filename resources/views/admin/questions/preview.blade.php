<div class="container-fluid p-0">
    <div class="card border-0">
        <div class="card-body">
            <div class="mb-4">
                <h5 class="card-title">{{ $question->question_text }}</h5>
                <p class="text-muted small">
                    <span class="badge {{ $question->difficulty == 'easy' ? 'bg-success' : ($question->difficulty == 'medium' ? 'bg-warning text-dark' : 'bg-danger') }} me-2">
                        {{ ucfirst($question->difficulty) }}
                    </span>
                    <span class="badge bg-info">{{ $question->points }} points</span>
                </p>
            </div>

            @if($question->image_url)
                <div class="mb-4 text-center">
                    <img src="{{ $question->image_url }}" class="img-fluid rounded" style="max-height: 200px;" alt="Question image">
                </div>
            @endif

            @if($question->video_url)
                <div class="mb-4">
                    <div class="ratio ratio-16x9">
                        <iframe src="{{ $question->video_url }}" title="Video" allowfullscreen></iframe>
                    </div>
                </div>
            @endif

            <div class="mb-4">
                <h6 class="fw-bold mb-3">Options:</h6>
                <div class="list-group">
                    @foreach($question->options as $key => $option)
                        <div class="list-group-item {{ $key == $question->correct_answer ? 'list-group-item-success' : '' }}">
                            <div class="d-flex align-items-center">
                                <span class="badge {{ $key == $question->correct_answer ? 'bg-success' : 'bg-secondary' }} me-3" style="width: 30px;">{{ $key }}</span>
                                <span>{{ $option }}</span>
                                @if($key == $question->correct_answer)
                                    <i class="bi bi-check-circle-fill text-success ms-auto"></i>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if($question->explanation)
                <div class="alert alert-info mb-0">
                    <h6 class="alert-heading fw-bold mb-2">
                        <i class="bi bi-info-circle me-2"></i>Explanation:
                    </h6>
                    <p class="mb-0">{{ $question->explanation }}</p>
                </div>
            @endif

            <div class="mt-4 text-muted small">
                <i class="bi bi-clock me-1"></i> Created: {{ $question->created_at ? $question->created_at->format('M d, Y H:i') : 'N/A' }}
                @if($question->updated_at && $question->updated_at != $question->created_at)
                    <br><i class="bi bi-pencil me-1"></i> Last updated: {{ $question->updated_at->format('M d, Y H:i') }}
                @endif
            </div>
        </div>
    </div>
</div>