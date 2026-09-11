<?php

namespace Stats4sd\FilamentTeamManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TestUserSeeder extends Seeder
{
    /**
     * @throws \Exception
     */
    public function run(): void
    {
        $guard = config('auth.defaults.guard', 'web');
        $usePrograms = (bool) config('filament-team-management.use_programs');

        // create roles (idempotent so the seeder can run over an already-seeded
        // permission baseline, e.g. the package test harness)
        $superAdmin = Role::findOrCreate('Super Admin', $guard);
        $programAdmin = Role::findOrCreate('Program Admin', $guard);

        // create permissions. The program pair only exists when programs are enabled.
        $basePermissions = [
            Permission::findOrCreate('access admin panel', $guard),
            Permission::findOrCreate('view all teams', $guard),
        ];

        $programPermissions = [];

        if ($usePrograms) {
            $programPermissions = [
                Permission::findOrCreate('access program admin panel', $guard),
                Permission::findOrCreate('view all programs', $guard),
            ];
        }

        // Make sure any freshly created permissions are visible to the checks
        // below (givePermissionTo / can) within the same request.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // attach permissions to roles so a seeded admin actually passes
        // CheckIfAdmin / CheckIfProgramAdmin without the host app needing its
        // own Gate::before.
        $superAdmin->givePermissionTo([...$basePermissions, ...$programPermissions]);

        if ($usePrograms) {
            $programAdmin->givePermissionTo($programPermissions);
        }

        // create users
        $user = config('filament-team-management.models.user')::updateOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => bcrypt('password'),
        ]);

        $admin = config('filament-team-management.models.user')::updateOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Test Admin',
            'password' => bcrypt('password'),
        ]);

        // assign role to the admin user
        if (method_exists($admin, 'assignRole')) {
            $admin->assignRole('Super Admin');
        } else {
            throw new \Exception('User model does not have assignRole method. Please make sure your User model uses the HasRoles trait. This can be achieved by extending the Stats4sd\FilamentTeamManagement\Models\User model');
        }
    }
}
