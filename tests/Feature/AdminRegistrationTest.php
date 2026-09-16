<?php

namespace Tests\Feature;

use App\Models\Invite;
use App\Models\User;
use App\Notifications\InviteCreated;
use App\Services\AppSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_switch_registration_mode(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->putJson(route('admin.registration.update'), ['mode' => 'public'])
            ->assertOk()
            ->assertJsonPath('mode', 'public');

        $this->assertTrue(AppSettings::isPublicRegistration());
    }

    public function test_non_admin_cannot_change_registration_mode(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson(route('admin.registration.update'), ['mode' => 'public'])
            ->assertForbidden();

        $this->assertTrue(AppSettings::isInviteOnly());
    }

    public function test_admin_can_create_and_revoke_invite_links(): void
    {
        $admin = User::factory()->admin()->create();

        $create = $this->actingAs($admin)
            ->postJson(route('admin.invites.store'), [
                'note' => 'Dave',
                'expires_in_days' => 7,
            ])
            ->assertOk();

        $inviteId = $create->json('invite.id');
        $this->assertNotEmpty($create->json('invite.url'));
        $this->assertDatabaseHas('invites', [
            'id' => $inviteId,
            'note' => 'Dave',
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.invites.revoke', $inviteId))
            ->assertOk();

        $this->assertNotNull(Invite::find($inviteId)->revoked_at);
    }

    public function test_sending_an_invite_requires_an_email_and_sends_mail(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson(route('admin.invites.store'), [
                'send' => true,
            ])
            ->assertUnprocessable();

        $this->actingAs($admin)
            ->postJson(route('admin.invites.store'), [
                'email' => 'friend@example.com',
                'note' => 'Dave',
                'send' => true,
            ])
            ->assertOk()
            ->assertJsonPath('sent', true);

        Notification::assertSentOnDemand(InviteCreated::class);
    }

    public function test_create_and_copy_does_not_send_mail(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson(route('admin.invites.store'), [
                'email' => 'friend@example.com',
                'send' => false,
            ])
            ->assertOk()
            ->assertJsonPath('sent', false);

        Notification::assertNothingSent();
    }

    public function test_admin_can_purge_all_unapproved_users(): void
    {
        $admin = User::factory()->admin()->create();
        $pending = User::factory()->create([
            'is_approved' => false,
            'created_at' => now()->subHour(),
        ]);
        $approved = User::factory()->create([
            'is_approved' => true,
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.users.purge-unapproved'))
            ->assertOk()
            ->assertJsonPath('deleted', 1);

        $this->assertDatabaseMissing('users', ['id' => $pending->id]);
        $this->assertDatabaseHas('users', ['id' => $approved->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
