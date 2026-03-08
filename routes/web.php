<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\QuizController;
use App\Http\Controllers\Web\AttemptController;
use App\Http\Controllers\Web\LeaderboardController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\QuizManagementController;
use App\Http\Controllers\Admin\QuestionManagementController;
use App\Http\Controllers\Admin\CategoryManagementController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\SettingsController;

Route::get('/', function () {
    return to_route('login');
});

// Social Login Routes
Route::get('/auth/{provider}/redirect', [App\Http\Controllers\Web\SocialiteController::class, 'redirect'])->name('social.login');
Route::get('/auth/{provider}/callback', [App\Http\Controllers\Web\SocialiteController::class, 'callback'])->name('social.callback');

// Quiz Listing (Public - anyone can view quizzes)
Route::get('/quizzes', [QuizController::class, 'index'])->name('quizzes.index');
Route::get('/quizzes/{slug}', [QuizController::class, 'show'])->name('quizzes.show');
Route::get('/category/{slug}', [QuizController::class, 'byCategory'])->name('quizzes.category');

// Leaderboard (Public)
Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');

// =============================================
// AUTHENTICATED USER ROUTES
// =============================================
Route::middleware(['auth'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Quiz Attempt Routes
    Route::get('/quiz/{quizId}/start', [AttemptController::class, 'start'])->name('quiz.start');
    
    Route::prefix('attempt')->name('attempt.')->group(function () {
        Route::post('/{attemptId}/submit-answer', [AttemptController::class, 'submitAnswer'])->name('submit-answer');
        Route::post('/{attemptId}/flag', [AttemptController::class, 'flagQuestion'])->name('flag');
        Route::post('/{attemptId}/complete', [AttemptController::class, 'complete'])->name('complete');
        Route::get('/{attemptId}/results', [AttemptController::class, 'results'])->name('results');
        Route::get('/{attemptId}/review', [AttemptController::class, 'review'])->name('review');
    });
    
    // User Profile Routes
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('index');
        Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
        Route::put('/update', [ProfileController::class, 'update'])->name('update');
        Route::post('/change-password', [ProfileController::class, 'changePassword'])->name('change-password');
        Route::get('/history', [ProfileController::class, 'history'])->name('history');
        Route::get('/achievements', [ProfileController::class, 'achievements'])->name('achievements');
    });
});

// =============================================
// ADMIN ROUTES
// =============================================
 // Add preview route outside the nested group (simpler URL)
 Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/admin/questions/{questionId}/preview', [QuestionManagementController::class, 'preview'])->name('admin.questions.preview');

        // Add duplicate route
        Route::post('/admin/questions/{questionId}/duplicate', [QuestionManagementController::class, 'duplicate'])->name('admin.questions.duplicate');
});
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    
    // Admin Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    
    // =========================================
    // Quiz Management
    // =========================================
    Route::prefix('quizzes')->name('quizzes.')->group(function () {
        // List all quizzes
        Route::get('/', [QuizManagementController::class, 'index'])->name('index');
        
        // Create new quiz
        Route::get('/create', [QuizManagementController::class, 'create'])->name('create');
        Route::post('/', [QuizManagementController::class, 'store'])->name('store');
        
        // Edit quiz
        Route::get('/{id}/edit', [QuizManagementController::class, 'edit'])->name('edit');
        Route::put('/{id}', [QuizManagementController::class, 'update'])->name('update');
        
        // Delete quiz
        Route::delete('/{id}', [QuizManagementController::class, 'destroy'])->name('destroy');
        
        // Publish/Unpublish quiz
        Route::post('/{id}/publish', [QuizManagementController::class, 'publish'])->name('publish');
        Route::post('/{id}/unpublish', [QuizManagementController::class, 'unpublish'])->name('unpublish');
        
        // =====================================
        // Question Management (Nested under quizzes)
        // =====================================
        Route::prefix('{quizId}/questions')->name('questions.')->group(function () {
            // List questions
            Route::get('/', [QuestionManagementController::class, 'index'])->name('index');
            
            // Create question
            Route::get('/create', [QuestionManagementController::class, 'create'])->name('create');
            Route::post('/', [QuestionManagementController::class, 'store'])->name('store');
            
            // Edit question
            Route::get('/{questionId}/edit', [QuestionManagementController::class, 'edit'])->name('edit');
            Route::put('/{questionId}', [QuestionManagementController::class, 'update'])->name('update');
            
            // Delete question
            Route::delete('/{questionId}', [QuestionManagementController::class, 'destroy'])->name('destroy');
            
            // Bulk upload questions via CSV
            Route::post('/bulk-upload', [QuestionManagementController::class, 'bulkUpload'])->name('bulk-upload');
            
            // Reorder questions (AJAX)
            Route::post('/reorder', [QuestionManagementController::class, 'reorder'])->name('reorder');

            Route::post('/bulk-delete', [QuestionManagementController::class, 'bulkDelete'])->name('bulk-delete');
            Route::post('/bulk-duplicate', [QuestionManagementController::class, 'bulkDuplicate'])->name('bulk-duplicate');
        });
        
    });

   
    
    // =========================================
    // Category Management
    // =========================================
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryManagementController::class, 'index'])->name('index');
        Route::get('/create', [CategoryManagementController::class, 'create'])->name('create');
        Route::post('/', [CategoryManagementController::class, 'store'])->name('store');
        Route::get('/{category}/edit', [CategoryManagementController::class, 'edit'])->name('edit');
        Route::put('/{category}', [CategoryManagementController::class, 'update'])->name('update');
        Route::delete('/{category}', [CategoryManagementController::class, 'destroy'])->name('destroy');
        Route::post('/reorder', [CategoryManagementController::class, 'reorder'])->name('reorder');
    });
    
    // =========================================
    // Reports & Analytics
    // =========================================
    Route::prefix('reports')->name('reports.')->group(function () {
        // Main reports dashboard
        Route::get('/', [ReportController::class, 'index'])->name('index');
        
        // User performance reports
        Route::get('/user-performance', [ReportController::class, 'userPerformance'])->name('user-performance');
        
        // Quiz analytics
        Route::get('/quiz-analytics', [ReportController::class, 'quizAnalytics'])->name('quiz-analytics');
        
        // Export reports
        Route::get('/export-user-report', [ReportController::class, 'exportUserReport'])->name('export-user');
        
        // Chart data endpoints (AJAX)
        Route::get('/chart-data', [ReportController::class, 'getChartData'])->name('chart-data');
    });

    // User Management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->name('index');
        Route::get('/{id}', [UserManagementController::class, 'show'])->name('show');
        Route::post('/{id}/toggle-status', [UserManagementController::class, 'toggleStatus'])->name('toggle-status');
        Route::delete('/{id}', [UserManagementController::class, 'destroy'])->name('destroy');
    });

       // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/clear-cache', [SettingsController::class, 'clearCache'])->name('settings.clear-cache');
});

require __DIR__.'/auth.php';
