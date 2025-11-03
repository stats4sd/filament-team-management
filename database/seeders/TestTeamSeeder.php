<?php

namespace Stats4sd\FilamentTeamManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Models\User;

class TestTeamSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table(config('filament-team-management.table_names.teams'))->delete();

        /** @var Team $team */
        $team = config('filament-team-management.models.team')::create([
            'name' => 'Test Team',
            'description' => fake()->paragraph(),
        ]);

        /** @var User $teamMember */
        $teamMember = config('filament-team-management.models.user')::create([
            'name' => 'Test Team Member',
            'email' => 'test-team-member@example.com',
            'password' => bcrypt('password'),
        ]);

        $teamAdmin = config('filament-team-management.models.user')::create([
            'name' => 'Test Team Admin',
            'email' => 'test-team-admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $team->members()->attach($teamMember->id);
        $team->admins()->attach($teamAdmin->id, ['is_admin' => true]);

    }
}
