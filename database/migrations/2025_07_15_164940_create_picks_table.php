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
        Schema::create('picks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('game_week_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->integer('points_earned')->nullable(); // null until result is known
            $table->enum('result', ['win', 'draw', 'loss'])->nullable(); // null until result is known
            $table->timestamp('picked_at');
            $table->timestamps();
            
            // Ensure a user can only pick one team per game week in a tournament
            $table->unique(['tournament_id', 'user_id', 'game_week_id']);
            
            // Ensure a user can't pick the same team twice in the same tournament
            $table->unique(['tournament_id', 'user_id', 'team_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('picks');
    }
};
