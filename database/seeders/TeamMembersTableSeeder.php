<?php

namespace Stats4sd\FilamentTeamManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeamMembersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        DB::table('team_members')->delete();

        DB::table('team_members')->insert(array(
            0 =>
            array(
                'id' => 1,
                'team_id' => 1,
                'user_id' => 1,
                'is_admin' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
        ));
    }
}
