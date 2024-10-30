<?php

namespace Stats4sd\FilamentTeamManagement\Database\Seeders;

use Illuminate\Database\Seeder;

// This is an example DatabaseSeeder file to be used in main repo.
// Suppose this file will not be used directly.
// Please refer to below code and update DatabaseSeeder.php in main repo.
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            TeamsTableSeeder::class,

            TestUserSeeder::class,
            ModelHasRolesTableSeeder::class,
            RoleHasPermissionsTableSeeder::class,

            TeamMembersTableSeeder::class,
        ]);
    }
}
