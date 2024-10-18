<?php

namespace Stats4sd\FilamentTeamManagement\Database\Seeders;

use Illuminate\Database\Seeder;

class RoleHasPermissionsTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('role_has_permissions')->delete();

        \DB::table('role_has_permissions')->insert([
            0 => [
                'permission_id' => 1,
                'role_id' => 1,
            ],
            1 => [
                'permission_id' => 2,
                'role_id' => 1,
            ],
            2 => [
                'permission_id' => 3,
                'role_id' => 1,
            ],
            3 => [
                'permission_id' => 4,
                'role_id' => 1,
            ],
            4 => [
                'permission_id' => 2,
                'role_id' => 2,
            ],
        ]);
    }
}
