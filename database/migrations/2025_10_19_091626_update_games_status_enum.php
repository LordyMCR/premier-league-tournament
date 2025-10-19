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
        Schema::table('games', function (Blueprint $table) {
            // Drop the existing enum constraint
            $table->dropColumn('status');
        });
        
        Schema::table('games', function (Blueprint $table) {
            // Add the status column with expanded enum values
            $table->enum('status', [
                'SCHEDULED', 
                'LIVE', 
                'IN_PLAY', 
                'PAUSED', 
                'TIMED', 
                'FINISHED', 
                'POSTPONED', 
                'CANCELLED'
            ])->default('SCHEDULED')->after('away_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        
        Schema::table('games', function (Blueprint $table) {
            $table->enum('status', ['SCHEDULED', 'LIVE', 'FINISHED', 'POSTPONED', 'CANCELLED'])->default('SCHEDULED')->after('away_score');
        });
    }
};
