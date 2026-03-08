<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run()
    {
       $categories = [
    [
        'name' => 'General Knowledge',
        'slug' => 'general-knowledge',
        'description' => 'Test your knowledge on various topics',
        'icon' => 'bi bi-globe',
        'order' => 1,
    ],
    [
        'name' => 'Science',
        'slug' => 'science',
        'description' => 'Physics, Chemistry, Biology and more',
        'icon' => 'bi bi-flask',
        'order' => 2,
    ],
    [
        'name' => 'Mathematics',
        'slug' => 'mathematics',
        'description' => 'Numbers, Algebra, Geometry and Calculus',
        'icon' => 'bi bi-calculator',
        'order' => 3,
    ],
    [
        'name' => 'History',
        'slug' => 'history',
        'description' => 'World history, ancient civilizations and modern events',
        'icon' => 'bi bi-clock-history',
        'order' => 4,
    ],
    [
        'name' => 'Geography',
        'slug' => 'geography',
        'description' => 'Countries, capitals, mountains and rivers',
        'icon' => 'bi bi-geo-alt',
        'order' => 5,
    ],
    [
        'name' => 'Literature',
        'slug' => 'literature',
        'description' => 'Books, authors and literary works',
        'icon' => 'bi bi-book',
        'order' => 6,
    ],
    [
        'name' => 'Sports',
        'slug' => 'sports',
        'description' => 'Football, basketball, cricket and more',
        'icon' => 'bi bi-trophy',
        'order' => 7,
    ],
    [
        'name' => 'Technology',
        'slug' => 'technology',
        'description' => 'Computers, programming, AI and gadgets',
        'icon' => 'bi bi-laptop',
        'order' => 8,
    ],
];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}