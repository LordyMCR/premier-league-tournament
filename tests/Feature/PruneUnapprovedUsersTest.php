<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneUnapprovedUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_stale_unapproved_users(): void
    {
        $stale = User::factory()->create([
            'is_approved' => false,
            'created_at' => now()->subDays(8),
        ]);
        $recent = User::factory()->create([
            'is_approved' => false,
            'created_at' => now()->subDay(),
        ]);
        $approved = User::factory()->create([
            'is_approved' => true,
            'created_at' => now()->subDays(30),
        ]);

        $this->artisan('users:prune-unapproved')->assertSuccessful();

        $this->assertDatabaseMissing('users', ['id' => $stale->id]);
        $this->assertDatabaseHas('users', ['id' => $recent->id]);
        $this->assertDatabaseHas('users', ['id' => $approved->id]);
    }

    public function test_days_zero_deletes_unapproved_users_of_any_age(): void
    {
        $recent = User::factory()->create([
            'is_approved' => false,
            'created_at' => now()->subHour(),
        ]);
        $approved = User::factory()->create([
            'is_approved' => true,
        ]);

        $this->artisan('users:prune-unapproved', ['--days' => 0])->assertSuccessful();

        $this->assertDatabaseMissing('users', ['id' => $recent->id]);
        $this->assertDatabaseHas('users', ['id' => $approved->id]);
    }
}
