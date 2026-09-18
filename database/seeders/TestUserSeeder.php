<?php

namespace Stats4sd\FilamentTeamManagement\Database\Seeders;

use Illuminate\Database\Seeder;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        config('filament-team-management.models.user')::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Example Member', 'password' => bcrypt('password')],
        );
    }
}
