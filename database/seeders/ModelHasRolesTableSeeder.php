<?php

namespace Stats4sd\FilamentTeamManagement\Database\Seeders;

use Illuminate\Database\Seeder;

class ModelHasRolesTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('model_has_roles')->delete();

        \DB::table('model_has_roles')->insert([
            0 => [
                'role_id' => 1,
                'model_type' => 'App\\Models\\User',
                'model_id' => 2,
            ],
        ]);

        \DB::table('model_has_roles')->insert([
            0 => [
                'role_id' => 2,
                'model_type' => 'App\\Models\\User',
                'model_id' => 3,
            ],
        ]);
    }
}
