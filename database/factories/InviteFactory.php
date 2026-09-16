<?php

namespace Database\Factories;

use App\Models\Invite;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invite>
 */
class InviteFactory extends Factory
{
    protected $model = Invite::class;

    public function definition(): array
    {
        return [
            'email' => null,
            'note' => null,
            'created_by' => User::factory(),
            'expires_at' => now()->addDays(14),
        ];
    }

    public function forEmail(string $email): static
    {
        return $this->state(fn () => ['email' => $email]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subDay()]);
    }

    public function used(): static
    {
        return $this->state(fn () => ['used_at' => now()]);
    }
}
