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
        Schema::create('leaderboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('total_score')->default(0);
            $table->integer('quizzes_completed')->default(0);
            $table->integer('total_attempts')->default(0);
            $table->float('average_score')->default(0);
            $table->integer('total_points')->default(0);
            $table->integer('rank')->default(0);
            $table->integer('weekly_rank')->default(0);
            $table->integer('monthly_rank')->default(0);
            $table->json('badges')->nullable();
            $table->timestamps();
            
            $table->index('rank');
            $table->index('weekly_rank');
            $table->index('total_points');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaderboards');
    }
};
