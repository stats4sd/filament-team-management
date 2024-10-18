<?php

namespace Stats4sd\FilamentTeamManagement\Database\Seeders;

use Illuminate\Database\Seeder;

class ProgramsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        \DB::table('programs')->delete();

        \DB::table('programs')->insert(array(
            0 =>
            array(
                'id' => 1,
                'name' => 'Test Program',
                'description' => NULL,
                'note' => NULL,
                'created_at' => '2024-10-14 11:00:00',
                'updated_at' => '2024-10-14 11:00:00',
            ),
        ));
    }
}
