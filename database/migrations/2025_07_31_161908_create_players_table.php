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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('external_id')->unique(); // API player ID
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('nationality')->nullable();
            $table->string('position'); // Goalkeeper, Defender, Midfielder, Attacker
            $table->string('detailed_position')->nullable(); // Centre-Back, Right-Back, etc.
            $table->integer('shirt_number')->nullable();
            $table->decimal('market_value', 12, 2)->nullable(); // in euros
            $table->string('contract_until')->nullable();
            $table->boolean('on_loan')->default(false);
            $table->foreignId('loan_from_team_id')->nullable()->constrained('teams')->onDelete('set null');
            $table->text('photo_url')->nullable();
            
            // Performance stats (calculated from match data)
            $table->integer('goals')->default(0);
            $table->integer('assists')->default(0);
            $table->integer('yellow_cards')->default(0);
            $table->integer('red_cards')->default(0);
            $table->integer('appearances')->default(0);
            $table->integer('minutes_played')->default(0);
            
            // Cache timestamps
            $table->timestamp('last_stats_update')->nullable();
            $table->timestamp('last_profile_update')->nullable();
            
            $table->timestamps();
            
            $table->index(['team_id', 'position']);
            $table->index('external_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
