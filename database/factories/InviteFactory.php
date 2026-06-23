<?php

namespace Stats4sd\FilamentTeamManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Stats4sd\FilamentTeamManagement\Models\Invite;

/**
 * @extends Factory<Invite>
 */
class InviteFactory extends Factory
{
    protected $model = Invite::class;

    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'inviter_id' => config('filament-team-management.models.user')::factory(),
            'token' => Str::random(24),
            'is_confirmed' => false,
        ];
    }

    /**
     * Mark the invite as confirmed (hidden by the onlyUnconfirmed global scope).
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_confirmed' => true,
        ]);
    }

    /**
     * Set an explicit token on the invite.
     */
    public function withToken(string $token): static
    {
        return $this->state(fn (array $attributes) => [
            'token' => $token,
        ]);
    }

    /**
     * Attach the invite to a team.
     */
    public function forTeam(mixed $team): static
    {
        return $this->state(fn (array $attributes) => [
            config('filament-team-management.column_names.teams_foreign_key') => $team instanceof Model ? $team->getKey() : $team,
        ]);
    }
}
