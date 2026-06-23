<?php

namespace Stats4sd\FilamentTeamManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Stats4sd\FilamentTeamManagement\Models\ProgramInvite;

/**
 * @extends Factory<ProgramInvite>
 */
class ProgramInviteFactory extends Factory
{
    protected $model = ProgramInvite::class;

    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'inviter_id' => config('filament-team-management.models.user')::factory(),
            'program_id' => config('filament-team-management.models.program')::factory(),
            'token' => Str::random(24),
            'is_confirmed' => false,
        ];
    }

    /**
     * Mark the program invite as confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_confirmed' => true,
        ]);
    }

    /**
     * Set an explicit token on the program invite.
     */
    public function withToken(string $token): static
    {
        return $this->state(fn (array $attributes) => [
            'token' => $token,
        ]);
    }
}
