<?php

namespace Stats4sd\FilamentTeamManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TestUserSeeder extends Seeder
{
    /**
     * @throws \Exception
     */
    public function run(): void
    {
        // create roles
        Role::create(['name' => 'Super Admin']);
        Role::create(['name' => 'Program Admin']);

        // create permissions
        Permission::create(['name' => 'access admin panel']);
        Permission::create(['name' => 'view all teams']);

        // create users
        $user = config('filament-team-management.models.user')::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $admin = config('filament-team-management.models.user')::create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        // assign role to users

        if (method_exists($user, 'assignRole')) {
            $admin->assignRole('Super Admin');
        } else {
            throw new \Exception('User model does not have assignRole method. Please make sure your User model uses the HasRoles trait. This can be achieved by extending the Stats4sd\FilamentTeamManagement\Models\User model');
        }

    }
}
