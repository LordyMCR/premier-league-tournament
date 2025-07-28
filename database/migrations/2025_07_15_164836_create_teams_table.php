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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., "Arsenal", "Chelsea"
            $table->string('short_name', 4)->unique(); // e.g., "ARS", "CHE"
            $table->string('logo_url')->nullable(); // URL to team logo
            $table->integer('external_id')->nullable()->unique(); // External API reference
            $table->string('primary_color', 7)->nullable(); // Hex color code
            $table->string('secondary_color', 7)->nullable(); // Hex color code
            $table->integer('founded')->nullable(); // Year founded
            $table->string('venue')->nullable(); // Stadium name
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
