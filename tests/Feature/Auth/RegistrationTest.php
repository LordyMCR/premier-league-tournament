<?php

namespace Tests\Feature\Auth;

use App\Models\Invite;
use App\Models\User;
use App\Services\AppSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invite_only_registration_screen_is_closed(): void
    {
        $this->assertTrue(AppSettings::isInviteOnly());

        $this->get('/register')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Register')
                ->where('mode', 'invite_closed'));
    }

    public function test_public_registration_is_rejected_while_invite_only(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('register'));

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_user_can_register_with_a_valid_invite_link(): void
    {
        $admin = User::factory()->admin()->create();
        $invite = Invite::factory()->create([
            'created_by' => $admin->id,
            'email' => null,
        ]);

        $this->get(route('register.invite', $invite->token))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Register')
                ->where('mode', 'invite')
                ->where('inviteEmail', null));

        $this->withSession(['register_form_started_at' => now()->subSeconds(5)->timestamp])
            ->post('/register', [
                'name' => 'Invited Friend',
                'email' => 'friend@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'invite_token' => $invite->token,
            ])
            ->assertRedirect(route('welcome', absolute: false));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'friend@example.com',
            'is_approved' => true,
            'invite_id' => $invite->id,
        ]);
        $this->assertNotNull($invite->fresh()->used_at);
    }

    public function test_invite_email_lock_is_enforced(): void
    {
        $admin = User::factory()->admin()->create();
        $invite = Invite::factory()->forEmail('locked@example.com')->create([
            'created_by' => $admin->id,
        ]);

        $this->get(route('register.invite', $invite->token))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Register')
                ->where('mode', 'invite')
                ->where('inviteEmail', 'locked@example.com'));

        $this->withSession(['register_form_started_at' => now()->subSeconds(5)->timestamp])
            ->from(route('register.invite', $invite->token))
            ->post('/register', [
                'name' => 'Invited Friend',
                'email' => 'other@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'invite_token' => $invite->token,
            ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_used_or_invalid_invite_shows_invalid_page(): void
    {
        $admin = User::factory()->admin()->create();
        $invite = Invite::factory()->used()->create([
            'created_by' => $admin->id,
        ]);

        $this->get(route('register.invite', $invite->token))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('mode', 'invite_invalid'));
    }

    public function test_public_registration_creates_unapproved_user_when_restrictions_enabled(): void
    {
        AppSettings::setRegistrationMode(AppSettings::REGISTRATION_PUBLIC);
        config(['app.restrictions_enabled' => true]);

        $this->withSession(['register_form_started_at' => now()->subSeconds(5)->timestamp])
            ->post('/register', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'is_approved' => false,
        ]);
    }

    public function test_public_registration_rejects_token_like_names(): void
    {
        AppSettings::setRegistrationMode(AppSettings::REGISTRATION_PUBLIC);

        $this->withSession(['register_form_started_at' => now()->subSeconds(5)->timestamp])
            ->from('/register')
            ->post('/register', [
                'name' => 'eNUsqC4PUe',
                'email' => 'bot@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseMissing('users', ['email' => 'bot@example.com']);
    }

    public function test_honeypot_does_not_create_an_account(): void
    {
        AppSettings::setRegistrationMode(AppSettings::REGISTRATION_PUBLIC);

        $this->withSession(['register_form_started_at' => now()->subSeconds(5)->timestamp])
            ->post('/register', [
                'name' => 'Test User',
                'email' => 'bot@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'website' => 'https://spam.example',
            ])
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('users', ['email' => 'bot@example.com']);
    }

    public function test_existing_users_can_still_log_in_while_invite_only(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
    }
}
