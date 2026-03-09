<?php

namespace Database\Seeders;

use App\Models\Quiz;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class QuizSeeder extends Seeder
{
    public function run()
    {
        $categories = Category::all();
        
        $quizzes = [
            [
                'title' => 'Ultimate General Knowledge Challenge',
                'description' => 'Test your knowledge on a wide range of topics including history, geography, science, and pop culture. Perfect for trivia enthusiasts!',
                'difficulty' => 'beginner',
                'time_limit' => 1,
                'passing_score' => 60,
                'max_attempts' => 0,
                'shuffle_questions' => true,
                'show_answers' => true,
                'points_per_question' => 10,
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'title' => 'Advanced Science & Technology Quiz',
                'description' => 'Challenge yourself with advanced questions about physics, chemistry, biology, and cutting-edge technology. For science enthusiasts!',
                'difficulty' => 'advanced',
                'time_limit' => 10,
                'passing_score' => 70,
                'max_attempts' => 3,
                'shuffle_questions' => true,
                'show_answers' => true,
                'points_per_question' => 15,
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'title' => 'World History: Ancient to Modern',
                'description' => 'Journey through time from ancient civilizations to modern events. Test your knowledge of historical figures, dates, and important events.',
                'difficulty' => 'intermediate',
                'time_limit' => 3,
                'passing_score' => 65,
                'max_attempts' => 0,
                'shuffle_questions' => true,
                'show_answers' => true,
                'points_per_question' => 10,
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'title' => 'Mathematics Mastery Test',
                'description' => 'From basic arithmetic to advanced calculus. Perfect for math lovers and those wanting to sharpen their numerical skills.',
                'difficulty' => 'expert',
                'time_limit' => 4,
                'passing_score' => 75,
                'max_attempts' => 2,
                'shuffle_questions' => false,
                'show_answers' => true,
                'points_per_question' => 20,
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'title' => 'Geography Explorer: Capitals & Landmarks',
                'description' => 'Explore the world through questions about countries, capitals, famous landmarks, and natural wonders.',
                'difficulty' => 'beginner',
                'time_limit' => 1,
                'passing_score' => 60,
                'max_attempts' => 0,
                'shuffle_questions' => true,
                'show_answers' => true,
                'points_per_question' => 10,
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'title' => 'Literature Classics Quiz',
                'description' => 'Test your knowledge of famous books, authors, and literary characters from around the world.',
                'difficulty' => 'intermediate',
                'time_limit' => 5,
                'passing_score' => 65,
                'max_attempts' => 0,
                'shuffle_questions' => true,
                'show_answers' => true,
                'points_per_question' => 10,
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'title' => 'Sports Trivia Challenge',
                'description' => 'From football to basketball, Olympics to World Cup. Test your sports knowledge!',
                'difficulty' => 'beginner',
                'time_limit' => 7,
                'passing_score' => 55,
                'max_attempts' => 0,
                'shuffle_questions' => true,
                'show_answers' => true,
                'points_per_question' => 10,
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'title' => 'Programming & Computer Science',
                'description' => 'Questions about programming languages, algorithms, data structures, and computer science concepts.',
                'difficulty' => 'advanced',
                'time_limit' => 8,
                'passing_score' => 70,
                'max_attempts' => 3,
                'shuffle_questions' => true,
                'show_answers' => true,
                'points_per_question' => 15,
                'is_published' => true,
                'published_at' => now(),
            ],
        ];

        foreach ($quizzes as $index => $quizData) {
            // Assign to different categories
            $category = $categories[$index % count($categories)];
            
            Quiz::create(array_merge($quizData, [
                'category_id' => $category->id,
                'slug' => Str::slug($quizData['title']),
                'total_questions' => 0, // Will be updated after questions are added
            ]));
        }

        $this->command->info('Quizzes seeded successfully!');
    }
}