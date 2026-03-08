<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    public function run()
    {
$achievements = [
    [
        'name' => 'First Steps',
        'slug' => 'first-steps',
        'description' => 'Complete your first quiz',
        'icon' => 'bi bi-star',
        'points_required' => 0,
        'criteria' => [
            'type' => 'quizzes_completed',
            'value' => 1,
        ],
    ],
    [
        'name' => 'Quiz Enthusiast',
        'slug' => 'quiz-enthusiast',
        'description' => 'Complete 10 quizzes',
        'icon' => 'bi bi-star-fill',
        'points_required' => 0,
        'criteria' => [
            'type' => 'quizzes_completed',
            'value' => 10,
        ],
    ],
    [
        'name' => 'Quiz Master',
        'slug' => 'quiz-master',
        'description' => 'Complete 50 quizzes',
        'icon' => 'bi bi-trophy',
        'points_required' => 0,
        'criteria' => [
            'type' => 'quizzes_completed',
            'value' => 50,
        ],
    ],
    [
        'name' => 'Perfect Score',
        'slug' => 'perfect-score',
        'description' => 'Get 100% on any quiz',
        'icon' => 'bi bi-award',
        'points_required' => 0,
        'criteria' => [
            'type' => 'perfect_score',
            'value' => 1,
        ],
    ],
    [
        'name' => 'Speed Demon',
        'slug' => 'speed-demon',
        'description' => 'Complete a quiz in half the time limit',
        'icon' => 'bi bi-lightning',
        'points_required' => 0,
        'criteria' => [
            'type' => 'speed_demon',
            'value' => 1,
        ],
    ],
    [
        'name' => 'Point Collector',
        'slug' => 'point-collector',
        'description' => 'Earn 1000 points',
        'icon' => 'bi bi-coin',
        'points_required' => 1000,
        'criteria' => [
            'type' => 'total_points',
            'value' => 1000,
        ],
    ],
    [
        'name' => 'Top Scorer',
        'slug' => 'top-scorer',
        'description' => 'Reach rank #1 on the leaderboard',
        'icon' => 'bi bi-crown',
        'points_required' => 0,
        'criteria' => [
            'type' => 'top_rank',
            'value' => 1,
        ],
    ],
    [
        'name' => 'Consistency King',
        'slug' => 'consistency-king',
        'description' => 'Maintain an average score above 90% for 10 quizzes',
        'icon' => 'bi bi-graph-up-arrow',
        'points_required' => 0,
        'criteria' => [
            'type' => 'average_score',
            'value' => 90,
        ],
    ],
];

        foreach ($achievements as $achievement) {
            Achievement::create($achievement);
        }
    }
}