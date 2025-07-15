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
        Schema::create('user_profile_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Profile Visibility Settings
            $table->boolean('profile_visible')->default(true);
            $table->boolean('show_real_name')->default(true);
            $table->boolean('show_email')->default(false);
            $table->boolean('show_location')->default(true);
            $table->boolean('show_age')->default(false);
            $table->boolean('show_favorite_team')->default(true);
            $table->boolean('show_supporter_since')->default(true);
            $table->boolean('show_social_links')->default(true);
            
            // Tournament Data Visibility
            $table->boolean('show_tournament_history')->default(true);
            $table->boolean('show_statistics')->default(true);
            $table->boolean('show_achievements')->default(true);
            $table->boolean('show_current_tournaments')->default(true);
            $table->boolean('show_pick_history')->default(false); // More private
            $table->boolean('show_team_preferences')->default(true);
            
            // Activity and Social Settings
            $table->boolean('show_last_active')->default(true);
            $table->boolean('show_join_date')->default(true);
            $table->boolean('allow_profile_views')->default(true);
            $table->boolean('count_profile_views')->default(true);
            $table->boolean('show_profile_view_count')->default(false);
            
            // Notification Preferences
            $table->boolean('notify_profile_views')->default(false);
            $table->boolean('notify_achievements')->default(true);
            $table->boolean('notify_tournament_invites')->default(true);
            $table->boolean('notify_weekly_summary')->default(true);
            
            // Display Preferences
            $table->enum('theme_preference', ['light', 'dark', 'auto'])->default('auto');
            $table->json('featured_achievements')->nullable(); // Array of achievement IDs to highlight
            $table->string('profile_badge_color', 7)->default('#10B981'); // Custom profile accent color
            $table->integer('max_featured_achievements')->default(3);
            
            // Advanced Privacy
            $table->boolean('searchable_by_email')->default(false);
            $table->boolean('searchable_by_name')->default(true);
            $table->boolean('allow_tournament_invites')->default(true);
            $table->boolean('public_leaderboard_participation')->default(true);
            
            $table->timestamps();
            
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profile_settings');
    }
};
