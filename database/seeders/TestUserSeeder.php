<?php

namespace Stats4sd\FilamentTeamManagement\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        // create roles
        $superAdminRole = Role::create(['name' => 'Super Admin']);
        $programAdminRole = Role::create(['name' => 'Program Admin']);

        // create permissions
        $permissions = [
            ['name' => 'access admin panel'],
            ['name' => 'access program admin panel'],
            ['name' => 'view all prorgrams'],
            ['name' => 'view all teams'],
        ];

        $superAdminRole->permissions()->createMany($permissions);

        // create users
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'latest_team_id' => 1,
            'latest_program_id' => null,
        ]);

        $admin = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'latest_team_id' => 1,
            'latest_program_id' => 1,
        ]);

        $programAdmin = User::create([
            'name' => 'Test Program Admin',
            'email' => 'program_admin@example.com',
            'password' => bcrypt('password'),
            'latest_team_id' => 2,
            'latest_program_id' => 1,
        ]);

        // assign role to users
        $admin->assignRole('Super Admin');
        $programAdmin->assignRole('Program Admin');
    }
}
