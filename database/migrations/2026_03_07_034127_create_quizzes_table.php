<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('featured_image')->nullable();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced', 'expert']);
            $table->integer('time_limit')->comment('Time limit in minutes');
            $table->integer('passing_score')->default(70);
            $table->integer('total_questions')->default(0);
            $table->integer('max_attempts')->default(0)->comment('0 for unlimited');
            $table->boolean('is_published')->default(false);
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('show_answers')->default(true);
            $table->integer('points_per_question')->default(10);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['category_id', 'difficulty', 'is_published']);
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
