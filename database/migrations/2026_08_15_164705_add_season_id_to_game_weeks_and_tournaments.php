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
            $table->foreignId('season_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->dropUnique(['week_number']);
            $table->unique(['season_id', 'week_number']);
        });

        Schema::table('tournaments', function (Blueprint $table) {
            $table->foreignId('season_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index('season_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('season_id');
        });

        Schema::table('game_weeks', function (Blueprint $table) {
            $table->dropUnique(['season_id', 'week_number']);
            $table->unique(['week_number']);
            $table->dropConstrainedForeignId('season_id');
        });
    }
};
