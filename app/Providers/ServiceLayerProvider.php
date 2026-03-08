<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Interfaces\QuizServiceInterface;
use App\Services\Interfaces\QuestionServiceInterface;
use App\Services\Interfaces\AttemptServiceInterface;
use App\Services\Interfaces\LeaderboardServiceInterface;
use App\Services\Interfaces\CategoryServiceInterface;
use App\Services\Interfaces\UserServiceInterface;
use App\Services\QuizService;
use App\Services\QuestionService;
use App\Services\AttemptService;
use App\Services\LeaderboardService;
use App\Services\CategoryService;
use App\Services\UserService;

class ServiceLayerProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(QuizServiceInterface::class, QuizService::class);
        $this->app->bind(QuestionServiceInterface::class, QuestionService::class);
        $this->app->bind(AttemptServiceInterface::class, AttemptService::class);
        $this->app->bind(LeaderboardServiceInterface::class, LeaderboardService::class);
        $this->app->bind(CategoryServiceInterface::class, CategoryService::class);
        $this->app->bind(UserServiceInterface::class, UserService::class);
    }

    public function boot()
    {
        //
    }
}