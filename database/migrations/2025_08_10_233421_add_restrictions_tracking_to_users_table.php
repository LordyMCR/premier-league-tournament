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
            $table->integer('avatar_changes_count')->default(0)->after('avatar');
            $table->boolean('is_approved')->default(true)->after('email_verified_at');
            $table->timestamp('approved_at')->nullable()->after('is_approved');
            $table->string('approval_token')->nullable()->after('approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar_changes_count', 'is_approved', 'approved_at', 'approval_token']);
        });
    }
};
