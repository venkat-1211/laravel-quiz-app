@extends('layouts.app')

@section('title', $quiz->title . ' - Attempt Quiz')

@push('styles')
<style>
    .quiz-timer {
        position: sticky;
        top: 20px;
        z-index: 1000;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        margin-bottom: 20px;
    }
    
    .timer-display {
        font-size: 2.5rem;
        font-weight: bold;
        text-align: center;
        font-family: 'Courier New', monospace;
    }
    
    .timer-warning {
        animation: pulse 1s infinite;
    }
    
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }
    
    .question-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        transition: transform 0.3s;
    }
    
    .question-card:hover {
        transform: translateY(-5px);
    }
    
    .option-item {
        padding: 15px;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .option-item:hover {
        background-color: #f8f9fa;
        border-color: #667eea;
    }
    
    .option-item.selected {
        background-color: #667eea;
        border-color: #667eea;
        color: white;
    }
    
    .option-item.selected .option-letter {
        background-color: white;
        color: #667eea;
    }
    
    .option-letter {
        display: inline-block;
        width: 30px;
        height: 30px;
        line-height: 30px;
        text-align: center;
        background-color: #667eea;
        color: white;
        border-radius: 50%;
        margin-right: 10px;
        font-weight: bold;
    }
    
    .nav-panel {
        position: sticky;
        top: 200px;
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .question-nav-item {
        display: inline-block;
        width: 40px;
        height: 40px;
        line-height: 40px;
        text-align: center;
        margin: 5px;
        border-radius: 50%;
        background-color: #f8f9fa;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        color: #333;
    }
    
    .question-nav-item:hover {
        background-color: #667eea;
        color: white;
        transform: scale(1.1);
    }
    
    .question-nav-item.current {
        background-color: #667eea;
        color: white;
        font-weight: bold;
    }
    
    .question-nav-item.answered {
        background-color: #28a745;
        color: white;
    }
    
    .question-nav-item.flagged {
        background-color: #ffc107;
        color: white;
        position: relative;
    }
    
    .question-nav-item.flagged::after {
        content: "⚑";
        position: absolute;
        top: -5px;
        right: -5px;
        font-size: 14px;
    }
    
    .progress-indicator {
        height: 8px;
        border-radius: 4px;
        margin-top: 20px;
    }
    
    .flag-button {
        transition: all 0.3s;
    }
    
    .flag-button.flagged {
        color: #ffc107;
        transform: scale(1.2);
    }
    
    .nav-buttons {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Timer -->
            <div class="quiz-timer" id="timerContainer">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-clock-history me-2"></i>
                        <span>Time Remaining</span>
                    </div>
                    <div class="timer-display" id="timer">
                        {{ gmdate('H:i:s', $quiz->time_limit * 60) }}
                    </div>
                </div>
                <div class="progress mt-2" style="height: 5px;">
                    <div class="progress-bar bg-warning" id="timerProgress" 
                         role="progressbar" style="width: 100%"></div>
                </div>
            </div>

            <!-- Questions Container -->
            <div id="questionsContainer">
                @foreach($questions as $index => $question)
                    <div class="question-card card" data-question-id="{{ $question['id'] }}" 
                         data-index="{{ $index }}" style="{{ $index > 0 ? 'display: none;' : '' }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">
                                    Question {{ $index + 1 }} of {{ count($questions) }}
                                </h5>
                                <button class="btn btn-sm btn-outline-warning flag-button" 
                                        onclick="toggleFlag({{ $attempt->id }}, {{ $question['id'] }})">
                                    <i class="bi bi-flag"></i> Flag
                                </button>
                            </div>
                            
                            <h4 class="mb-4">{{ $question['text'] }}</h4>
                            
                            @if(isset($question['image_url']))
                                <img src="{{ $question['image_url'] }}" 
                                     class="img-fluid mb-4 rounded" alt="Question image">
                            @endif
                            
                            <div class="options-container">
                                @foreach($question['options'] as $key => $option)
                                    <div class="option-item" 
                                         onclick="selectOption({{ $attempt->id }}, {{ $question['id'] }}, '{{ $key }}')">
                                        <span class="option-letter">{{ $key }}</span>
                                        {{ $option }}
                                    </div>
                                @endforeach
                            </div>
                            
                            <div class="nav-buttons">
                                <button class="btn btn-outline-secondary" 
                                        onclick="previousQuestion()" 
                                        {{ $index == 0 ? 'disabled' : '' }}>
                                    <i class="bi bi-chevron-left"></i> Previous
                                </button>
                                @if($index < count($questions) - 1)
                                    <button class="btn btn-primary" 
                                            onclick="nextQuestion()">
                                        Next <i class="bi bi-chevron-right"></i>
                                    </button>
                                @else
                                    <button class="btn btn-success" 
                                            onclick="submitQuiz()">
                                        Submit Quiz <i class="bi bi-check-circle"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        
        <!-- Navigation Panel -->
        <div class="col-lg-4">
            <div class="nav-panel">
                <h5 class="mb-3">Question Navigator</h5>
                <div class="mb-3">
                    <div class="d-flex gap-2 mb-2">
                        <span class="badge bg-success">Answered</span>
                        <span class="badge bg-warning">Flagged</span>
                        <span class="badge bg-primary">Current</span>
                    </div>
                </div>
                <div id="questionNavigator" class="mb-4">
                    @foreach($questions as $index => $question)
                        <a href="#" class="question-nav-item" data-index="{{ $index }}"
                           onclick="goToQuestion({{ $index }}); return false;">
                            {{ $index + 1 }}
                        </a>
                    @endforeach
                </div>
                
                <div class="progress-indicator">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Progress</span>
                        <span id="progressCount">0/{{ count($questions) }}</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-success" id="progressBar" 
                             role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
                
                <hr>
                
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary" onclick="submitQuiz()">
                        <i class="bi bi-check-circle me-2"></i>
                        Submit Quiz
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Submit Confirmation Modal -->
<div class="modal fade" id="submitModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Submit Quiz</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to submit your quiz?</p>
                <p class="mb-0">
                    <strong>Questions answered:</strong> 
                    <span id="answeredCount">0</span>/{{ count($questions) }}
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancel
                </button>
                <form method="POST" id="submitQuizForm" action="{{ route('attempt.complete', $attempt->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        Submit Quiz
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Auto-submit Modal -->
<div class="modal fade" id="timeoutModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Time's Up!</h5>
            </div>
            <div class="modal-body">
                <p>Your time is over. Your quiz will be submitted automatically.</p>
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentQuestionIndex = 0;
    const totalQuestions = {{ count($questions) }};
    const attemptId = {{ $attempt->id }};
    let timeLimit = {{ $quiz->time_limit * 60 }}; // in seconds
    let timerInterval;
    let answeredQuestions = new Set();
    let flaggedQuestions = new Set();
    let questionTimes = {};
    let startTime = Date.now();

    // Initialize timer
    function startTimer() {
        const timerDisplay = document.getElementById('timer');
        const timerProgress = document.getElementById('timerProgress');
        
        timerInterval = setInterval(() => {
            if (timeLimit <= 0) {
                clearInterval(timerInterval);
                autoSubmitQuiz();
                return;
            }
            
            timeLimit--;
            
            // Update display
            const hours = Math.floor(timeLimit / 3600);
            const minutes = Math.floor((timeLimit % 3600) / 60);
            const seconds = timeLimit % 60;
            
            timerDisplay.textContent = 
                `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            // Update progress bar
            const totalTime = {{ $quiz->time_limit * 60 }};
            const percentage = (timeLimit / totalTime) * 100;
            timerProgress.style.width = percentage + '%';
            
            // Warning classes
            if (timeLimit <= 300) { // 5 minutes
                timerDisplay.classList.add('timer-warning', 'text-danger');
            }
            
            // Auto save current question time
            const currentQId = getCurrentQuestionId();
            if (currentQId) {
                questionTimes[currentQId] = Math.floor((Date.now() - startTime) / 1000);
            }
        }, 1000);
    }

    // Select option
    function selectOption(attemptId, questionId, answer) {
        const questionCard = document.querySelector(`[data-question-id="${questionId}"]`);
        const options = questionCard.querySelectorAll('.option-item');
        
        // Remove selected class from all options
        options.forEach(opt => opt.classList.remove('selected'));
        
        // Add selected class to chosen option
        event.currentTarget.classList.add('selected');
        
        // Send answer to server
        const timeSpent = questionTimes[questionId] || 0;
        
        fetch(`/attempt/${attemptId}/submit-answer`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                question_id: questionId,
                answer: answer,
                time_spent: timeSpent
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mark as answered
                answeredQuestions.add(questionId);
                updateNavigator();
                updateProgress();
                
                // Auto move to next question
                setTimeout(() => {
                    if (currentQuestionIndex < totalQuestions - 1) {
                        nextQuestion();
                    }
                }, 500);
            }
        });
    }

    // Toggle flag
    function toggleFlag(attemptId, questionId) {
        const flagButton = event.currentTarget;
        const isFlagged = flagButton.classList.contains('flagged');
        
        fetch(`/attempt/${attemptId}/flag`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                question_id: questionId,
                flag: !isFlagged
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                flagButton.classList.toggle('flagged');
                
                if (!isFlagged) {
                    flaggedQuestions.add(questionId);
                } else {
                    flaggedQuestions.delete(questionId);
                }
                
                updateNavigator();
            }
        });
    }

    // Navigation
    function nextQuestion() {
        if (currentQuestionIndex < totalQuestions - 1) {
            showQuestion(currentQuestionIndex + 1);
        }
    }

    function previousQuestion() {
        if (currentQuestionIndex > 0) {
            showQuestion(currentQuestionIndex - 1);
        }
    }

    function goToQuestion(index) {
        showQuestion(index);
    }

    function showQuestion(index) {
        // Hide current question
        document.querySelectorAll('.question-card').forEach(q => {
            q.style.display = 'none';
        });
        
        // Show new question
        document.querySelector(`.question-card[data-index="${index}"]`).style.display = 'block';
        
        // Update navigator
        document.querySelectorAll('.question-nav-item').forEach(item => {
            item.classList.remove('current');
        });
        document.querySelector(`.question-nav-item[data-index="${index}"]`).classList.add('current');
        
        currentQuestionIndex = index;
        startTime = Date.now();
    }

    function updateNavigator() {
        document.querySelectorAll('.question-nav-item').forEach(item => {
            const index = item.dataset.index;
            const questionId = document.querySelector(`.question-card[data-index="${index}"]`).dataset.questionId;
            
            item.classList.remove('answered', 'flagged');
            
            if (answeredQuestions.has(parseInt(questionId))) {
                item.classList.add('answered');
            }
            if (flaggedQuestions.has(parseInt(questionId))) {
                item.classList.add('flagged');
            }
        });
    }

    function updateProgress() {
        const answered = answeredQuestions.size;
        document.getElementById('progressCount').textContent = `${answered}/${totalQuestions}`;
        document.getElementById('progressBar').style.width = (answered / totalQuestions * 100) + '%';
        document.getElementById('answeredCount').textContent = answered;
    }

    function getCurrentQuestionId() {
        const currentCard = document.querySelector(`.question-card[data-index="${currentQuestionIndex}"]`);
        return currentCard ? parseInt(currentCard.dataset.questionId) : null;
    }

    function submitQuiz() {
        const modal = new bootstrap.Modal(document.getElementById('submitModal'));
        modal.show();
    }

    function autoSubmitQuiz() {
        const timeoutModal = new bootstrap.Modal(document.getElementById('timeoutModal'));
        timeoutModal.show();
        
        // Auto submit after 3 seconds
        setTimeout(() => {
            document.getElementById('submitQuizForm').submit();
        }, 3000);
    }

    // Start timer on page load
    document.addEventListener('DOMContentLoaded', function() {
        startTimer();
        
        // Save initial navigator state
        updateNavigator();
    });
</script>
@endpush