<?php

namespace Stats4sd\FilamentTeamManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Stats4sd\FilamentTeamManagement\Models\Program;
use Stats4sd\FilamentTeamManagement\Models\Team;

class TestProgramSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table(config('filament-team-management.table_names.programs'))->delete();

        $program = config('filament-team-management.models.program')::create([
            'name' => 'Test Program',
            'description' => fake()->paragraph(),
        ]);

        // create teams to link to the program
        /** @var Team $programTeamOne */
        $programTeamOne = $program->teams()->create([
            'name' => 'Test Program Team One',
            'description' => fake()->paragraph(),
        ]);

        /** @var Team $programTeamTwo */
        $programTeamTwo = $program->teams()->create([
            'name' => 'Test Program Team Two',
            'description' => fake()->paragraph(),
        ]);

        // create users to link to the teams
        $teamMemberOne = config('filament-team-management.models.user')::create([
            'name' => 'Test Program Team Member One',
            'email' => 'program-team-one-member@example.com',
            'password' => bcrypt('password'),
        ]);

        $teamMemberTwo = config('filament-team-management.models.user')::create([
            'name' => 'Test Program Team Member Two',
            'email' => 'program-team-two-member@example.com',
            'password' => bcrypt('password'),
        ]);

        $programTeamOne->members()->attach($teamMemberOne->id, ['is_admin' => true]);
        $programTeamTwo->members()->attach($teamMemberTwo->id, ['is_admin' => true]);

    }
}
