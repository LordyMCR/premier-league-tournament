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
        Schema::table('picks', function (Blueprint $table) {
            // Add home_away field to track if team was playing home or away
            $table->enum('home_away', ['home', 'away'])->nullable()->after('team_id');
            
            // Drop the old unique constraint that prevented same team being picked twice
            $table->dropUnique(['tournament_id', 'user_id', 'team_id']);
            
            // Add new unique constraint that allows same team twice but different home/away
            $table->unique(['tournament_id', 'user_id', 'team_id', 'home_away'], 'picks_tournament_user_team_home_away_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('picks', function (Blueprint $table) {
            // Drop the new unique constraint
            $table->dropUnique('picks_tournament_user_team_home_away_unique');
            
            // Re-add the old unique constraint
            $table->unique(['tournament_id', 'user_id', 'team_id']);
            
            // Drop the home_away column
            $table->dropColumn('home_away');
        });
    }
};
