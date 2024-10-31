<?php

namespace Stats4sd\FilamentTeamManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class TestUserSeeder extends Seeder
{
    /**
     * @throws \Exception
     */
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
        $user = config('filament-team-management.models.user')::create([
            'name' => 'Test User',
            'email' => 'test-from-team@example.com',
            'password' => bcrypt('password'),
        ]);

        $admin = config('filament-team-management.models.user')::create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $programAdmin = config('filament-team-management.models.user')::create([
            'name' => 'Test Program Admin',
            'email' => 'program_admin@example.com',
            'password' => bcrypt('password'),
        ]);

        // assign role to users

        if (method_exists($user, 'assignRole')) {
            $admin->assignRole('Super Admin');
            $programAdmin->assignRole('Program Admin');
        } else {
            throw new \Exception('User model does not have assignRole method. Please make sure your User model uses the HasRoles trait. This can be achieved by extending the Stats4sd\FilamentTeamManagement\Models\User model');
        }

    }
}
