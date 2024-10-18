<?php

namespace Stats4sd\FilamentTeamManagement\Database\Seeders;

use Illuminate\Database\Seeder;

class ProgramUserTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        \DB::table('program_user')->delete();

        \DB::table('program_user')->insert(array(
            0 =>
            array(
                'id' => 1,
                'program_id' => 1,
                'user_id' => 3,
                'created_at' => '2024-10-14 10:12:32',
                'updated_at' => '2024-10-14 10:12:32',
            ),
        ));
    }
}
