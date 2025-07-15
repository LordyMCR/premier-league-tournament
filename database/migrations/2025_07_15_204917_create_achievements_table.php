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
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('icon')->nullable(); // Font awesome icon class or image path
            $table->string('color', 7)->default('#10B981'); // Hex color for badge
            $table->enum('category', ['tournament', 'social', 'picks', 'streak', 'special'])->default('tournament');
            $table->enum('rarity', ['common', 'rare', 'epic', 'legendary'])->default('common');
            $table->json('criteria'); // JSON object describing how to earn this achievement
            $table->integer('points')->default(0); // Points awarded for earning this achievement
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0); // Display order
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
