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
        Schema::create('live_match_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->integer('home_score')->default(0);
            $table->integer('away_score')->default(0);
            $table->string('status', 20)->default('SCHEDULED'); // SCHEDULED, LIVE, PAUSED, FINISHED
            $table->integer('minute')->nullable(); // Current match minute
            $table->json('events')->nullable(); // Goals, cards, substitutions
            $table->timestamp('last_updated')->useCurrent();
            $table->timestamps();
            
            $table->index(['game_id', 'status']);
            $table->index('last_updated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_match_events');
    }
};
