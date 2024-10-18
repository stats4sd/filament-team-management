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

        \DB::table('programs')->insert([
            0 => [
                'id' => 1,
                'name' => 'Test Program',
                'description' => null,
                'note' => null,
                'created_at' => '2024-10-14 11:00:00',
                'updated_at' => '2024-10-14 11:00:00',
            ],
        ]);
    }
}
