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
        Schema::table('game_weeks', function (Blueprint $table) {
            $table->dateTime('gameweek_start_time')->nullable()->after('start_date');
            $table->dateTime('gameweek_end_time')->nullable()->after('end_date');
            $table->dateTime('selection_deadline')->nullable()->after('gameweek_end_time');
            $table->dateTime('selection_opens')->nullable()->after('selection_deadline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_weeks', function (Blueprint $table) {
            $table->dropColumn(['gameweek_start_time', 'gameweek_end_time', 'selection_deadline', 'selection_opens']);
        });
    }
};
