<?php

namespace Stats4sd\FilamentTeamManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Stats4sd\FilamentTeamManagement\Models\Program;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    protected $model = Program::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->paragraph(),
            'note' => fake()->sentence(),
        ];
    }
}
