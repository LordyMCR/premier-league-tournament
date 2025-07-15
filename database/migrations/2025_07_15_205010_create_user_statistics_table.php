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
        Schema::create('user_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Tournament Performance
            $table->integer('total_tournaments')->default(0);
            $table->integer('tournaments_won')->default(0);
            $table->integer('tournaments_completed')->default(0);
            $table->integer('tournaments_active')->default(0);
            
            // Points and Scoring
            $table->integer('total_points')->default(0);
            $table->decimal('average_points_per_tournament', 8, 2)->default(0);
            $table->integer('highest_tournament_score')->default(0);
            $table->integer('lowest_tournament_score')->nullable();
            
            // Pick Statistics
            $table->integer('total_picks')->default(0);
            $table->integer('winning_picks')->default(0);
            $table->integer('drawing_picks')->default(0);
            $table->integer('losing_picks')->default(0);
            $table->decimal('win_percentage', 5, 2)->default(0);
            
            // Streaks and Achievements
            $table->integer('current_win_streak')->default(0);
            $table->integer('longest_win_streak')->default(0);
            $table->integer('current_tournament_streak')->default(0); // Consecutive tournaments played
            $table->integer('longest_tournament_streak')->default(0);
            
            // Favorite Teams Analysis
            $table->json('team_pick_counts')->nullable(); // JSON: {team_id: count}
            $table->json('team_success_rates')->nullable(); // JSON: {team_id: success_rate}
            $table->foreignId('most_picked_team_id')->nullable()->constrained('teams');
            $table->foreignId('most_successful_team_id')->nullable()->constrained('teams');
            
            // Seasonal Performance
            $table->json('monthly_performance')->nullable(); // JSON: {month-year: {tournaments: X, points: Y}}
            $table->integer('best_month_points')->default(0);
            $table->string('best_month')->nullable(); // 'YYYY-MM'
            
            // Social Stats
            $table->integer('profile_views_received')->default(0);
            $table->integer('profile_views_given')->default(0);
            $table->integer('achievements_earned')->default(0);
            $table->integer('achievement_points')->default(0);
            
            // Ranks and Positions
            $table->integer('best_tournament_position')->nullable();
            $table->integer('average_tournament_position')->nullable();
            $table->integer('global_ranking_position')->nullable();
            
            // Activity Metrics
            $table->timestamp('first_tournament_at')->nullable();
            $table->timestamp('last_tournament_at')->nullable();
            $table->timestamp('last_pick_at')->nullable();
            $table->integer('days_active')->default(0);
            
            $table->timestamps();
            
            $table->unique('user_id');
            $table->index(['total_points', 'tournaments_won']);
            $table->index(['win_percentage', 'total_picks']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_statistics');
    }
};
