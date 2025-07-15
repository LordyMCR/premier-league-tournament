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
        Schema::table('users', function (Blueprint $table) {
            // Profile Information
            $table->string('avatar')->nullable()->after('email');
            $table->text('bio')->nullable()->after('avatar');
            $table->string('location')->nullable()->after('bio');
            $table->date('date_of_birth')->nullable()->after('location');
            
            // Premier League Related
            $table->foreignId('favorite_team_id')->nullable()->constrained('teams')->after('date_of_birth');
            $table->string('supporter_since')->nullable()->after('favorite_team_id'); // Year they started supporting
            
            // Social Links
            $table->string('twitter_handle')->nullable()->after('supporter_since');
            $table->string('instagram_handle')->nullable()->after('twitter_handle');
            
            // Display Preferences
            $table->string('display_name')->nullable()->after('instagram_handle');
            $table->boolean('show_real_name')->default(true)->after('display_name');
            $table->boolean('show_email')->default(false)->after('show_real_name');
            $table->boolean('show_location')->default(true)->after('show_email');
            $table->boolean('show_age')->default(false)->after('show_location');
            $table->boolean('profile_public')->default(true)->after('show_age');
            
            // Tournament Stats (cache for performance)
            $table->integer('tournaments_played')->default(0)->after('profile_public');
            $table->integer('tournaments_won')->default(0)->after('tournaments_played');
            $table->integer('total_points')->default(0)->after('tournaments_won');
            $table->decimal('average_points', 8, 2)->default(0)->after('total_points');
            $table->integer('best_finish')->nullable()->after('average_points');
            $table->integer('current_streak')->default(0)->after('best_finish');
            $table->integer('longest_streak')->default(0)->after('current_streak');
            
            // Activity
            $table->timestamp('last_active_at')->nullable()->after('longest_streak');
            $table->integer('profile_views')->default(0)->after('last_active_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['favorite_team_id']);
            $table->dropColumn([
                'avatar',
                'bio',
                'location',
                'date_of_birth',
                'favorite_team_id',
                'supporter_since',
                'twitter_handle',
                'instagram_handle',
                'display_name',
                'show_real_name',
                'show_email',
                'show_location',
                'show_age',
                'profile_public',
                'tournaments_played',
                'tournaments_won',
                'total_points',
                'average_points',
                'best_finish',
                'current_streak',
                'longest_streak',
                'last_active_at',
                'profile_views'
            ]);
        });
    }
};
