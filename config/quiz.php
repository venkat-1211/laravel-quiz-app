<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Quiz Configuration
    |--------------------------------------------------------------------------
    */

    'difficulty_levels' => [
        'beginner',
        'intermediate',
        'advanced',
        'expert',
    ],

    'question_difficulty' => [
        'easy',
        'medium',
        'hard',
    ],

    'default_points_per_question' => 10,

    'max_time_limit' => 300, // minutes

    'max_questions_per_quiz' => 100,

    'cache' => [
        'leaderboard_ttl' => 300, // 5 minutes
        'quiz_list_ttl' => 3600, // 1 hour
        'category_list_ttl' => 3600, // 1 hour
    ],

    'pagination' => [
        'quizzes_per_page' => 12,
        'users_per_page' => 15,
        'attempts_per_page' => 15,
    ],

    'file_uploads' => [
        'max_image_size' => 2048, // KB
        'max_csv_size' => 10240, // KB
        'allowed_image_types' => ['jpg', 'jpeg', 'png', 'gif'],
    ],
];