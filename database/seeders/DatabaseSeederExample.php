<?php

namespace Stats4sd\FilamentTeamManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Stats4sd\FilamentOdkLink\Database\Seeders\PlatformSeeder;

// This is an example DatabaseSeeder file to be used in main repo.
// Suppose this file will not be used directly.
// Please refer to below code and update DatabaseSeeder.php in main repo.
class DatabaseSeederExample extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PlatformSeeder::class);

        $this->call([
            ProgramsTableSeeder::class,
            TeamsTableSeeder::class,
            ProgramTeamTableSeeder::class,

            TestUserSeeder::class,
            ModelHasRolesTableSeeder::class,
            RoleHasPermissionsTableSeeder::class,

            ProgramUserTableSeeder::class,
            TeamMembersTableSeeder::class,
        ]);
    }
}
