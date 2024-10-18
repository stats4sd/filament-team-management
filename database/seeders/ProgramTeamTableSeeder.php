<?php

namespace Stats4sd\FilamentTeamManagement\Database\Seeders;

use Illuminate\Database\Seeder;

class ProgramTeamTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('program_team')->delete();

        \DB::table('program_team')->insert([
            0 => [
                'id' => 1,
                'program_id' => 1,
                'team_id' => 2,
                'created_at' => '2024-10-14 11:00:00',
                'updated_at' => '2024-10-14 11:00:00',
            ],
            1 => [
                'id' => 2,
                'program_id' => 1,
                'team_id' => 3,
                'created_at' => '2024-10-14 11:00:00',
                'updated_at' => '2024-10-14 11:00:00',
            ],
        ]);
    }
}
