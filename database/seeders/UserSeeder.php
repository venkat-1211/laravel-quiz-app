<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Leaderboard;
use App\Models\Achievement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('12345678'),
            'email_verified_at' => now(),
            'bio' => 'System administrator and quiz master.',
            'avatar' => null,
            'last_login_at' => now(),
            'last_login_ip' => '127.0.0.1',
            'is_active' => true,
        ]);
        $admin->addRole('admin');

        // Create regular users
        $users = [
            [
                'name' => 'User 1',
                'email' => 'user1@gmail.com',
                'bio' => 'Quiz enthusiast and lifelong learner.',
                'avatar' => null,
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'bio' => 'Science lover and trivia champion.',
                'avatar' => null,
            ],
            [
                'name' => 'Mike Johnson',
                'email' => 'mike@example.com',
                'bio' => 'Sports fanatic and history buff.',
                'avatar' => null,
            ],
            [
                'name' => 'Sarah Williams',
                'email' => 'sarah@example.com',
                'bio' => 'Literature graduate and quiz master.',
                'avatar' => null,
            ],
            [
                'name' => 'David Brown',
                'email' => 'david@example.com',
                'bio' => 'Technology professional and gamer.',
                'avatar' => null,
            ],
            [
                'name' => 'Emily Davis',
                'email' => 'emily@example.com',
                'bio' => 'Mathematics teacher and puzzle solver.',
                'avatar' => null,
            ],
            [
                'name' => 'Robert Wilson',
                'email' => 'robert@example.com',
                'bio' => 'Geography enthusiast and traveler.',
                'avatar' => null,
            ],
            [
                'name' => 'Lisa Anderson',
                'email' => 'lisa@example.com',
                'bio' => 'Art lover and culture explorer.',
                'avatar' => null,
            ],
            [
                'name' => 'James Taylor',
                'email' => 'james@example.com',
                'bio' => 'Music lover and trivia addict.',
                'avatar' => null,
            ],
            [
                'name' => 'Maria Garcia',
                'email' => 'maria@example.com',
                'bio' => 'Language enthusiast and polyglot.',
                'avatar' => null,
            ],
        ];

        foreach ($users as $userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
                'bio' => $userData['bio'],
                'avatar' => $userData['avatar'],
                'last_login_at' => now()->subDays(rand(1, 30)),
                'last_login_ip' => '127.0.0.1',
                'is_active' => true,
            ]);
            $user->addRole('user');

            // Create leaderboard entry for user
            $this->createLeaderboardEntry($user);
            
            // Award some achievements to random users
            if (rand(1, 100) <= 70) { // 70% chance
                $this->awardRandomAchievements($user);
            }
        }

        $this->command->info('Users seeded successfully!');
    }

    private function createLeaderboardEntry($user)
    {
        // Generate random stats
        $quizzesCompleted = rand(5, 50);
        $totalPoints = $quizzesCompleted * rand(80, 150);
        $averageScore = rand(60, 98);

        Leaderboard::create([
            'user_id' => $user->id,
            'total_points' => $totalPoints,
            'quizzes_completed' => $quizzesCompleted,
            'total_attempts' => $quizzesCompleted + rand(0, 10),
            'average_score' => $averageScore,
            'rank' => 0, // Will be updated by command
            'weekly_rank' => 0,
            'monthly_rank' => 0,
            'badges' => [],
        ]);
    }

    private function awardRandomAchievements($user)
    {
        $achievements = Achievement::all();
        $numberToAward = rand(1, 3);
        
        $selectedAchievements = $achievements->random(min($numberToAward, $achievements->count()));

        foreach ($selectedAchievements as $achievement) {
            // Check if user already has this achievement
            if (!$user->achievements()->where('achievement_id', $achievement->id)->exists()) {
                $user->achievements()->attach($achievement->id, ['earned_at' => now()->subDays(rand(1, 30))]);
                
                // Update leaderboard badges
                $leaderboard = $user->leaderboard;
                if ($leaderboard) {
                    $badges = $leaderboard->badges ?? [];
                    $badges[] = [
                        'id' => $achievement->id,
                        'name' => $achievement->name,
                        'icon' => $achievement->icon,
                        'earned_at' => now()->subDays(rand(1, 30))->toDateTimeString(),
                    ];
                    $leaderboard->badges = $badges;
                    $leaderboard->save();
                }
            }
        }
    }
}