<?php

namespace Stats4sd\FilamentTeamManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Stats4sd\FilamentTeamManagement\Models\Team;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'description' => fake()->paragraph(),
        ];
    }
}
