<?php

namespace Stats4sd\FilamentTeamManagement\Database\Seeders;

use Illuminate\Database\Seeder;

class TeamsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        \DB::table('teams')->delete();

        \DB::table('teams')->insert(array(
            0 =>
            array(
                'id' => 1,
                'name' => 'Test User Team',
                'website' => NULL,
                'description' => 'Important Note: This team is created by seeder file. It does not have ODK project ID. Please create a new team for xlsform related testing',
                'created_at' => '2024-08-15 10:22:09',
                'updated_at' => '2024-08-15 10:22:09',
            ),
            1 =>
            array(
                'id' => 2,
                'name' => 'TP Team 11',
                'website' => NULL,
                'description' => 'Important Note: This team is created by seeder file. It does not have ODK project ID. Please create a new team for xlsform related testing',
                'created_at' => '2024-10-14 11:00:00',
                'updated_at' => '2024-10-14 11:00:00',
            ),
            2 =>
            array(
                'id' => 3,
                'name' => 'TP Team 12',
                'website' => NULL,
                'description' => 'Important Note: This team is created by seeder file. It does not have ODK project ID. Please create a new team for xlsform related testing',
                'created_at' => '2024-10-14 11:00:00',
                'updated_at' => '2024-10-14 11:00:00',
            ),
        ));
    }
}
