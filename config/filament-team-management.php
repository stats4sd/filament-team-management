<?php

// config for Stats4sd/FilamentTeamManagement
use Stats4sd\FilamentTeamManagement\Models\Program;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Models\User;

return [
    'use_programs' => env('FILAMENT_TEAM_MANAGEMENT_USE_PROGRAMS', false),

    'models' => [
        'user' => env('FILAMENT_TEAM_MANAGEMENT_USER_MODEL', User::class),
        'team' => env('FILAMENT_TEAM_MANAGEMENT_TEAM_MODEL', Team::class),
        'program' => env('FILAMENT_TEAM_MANAGEMENT_PROGRAM_MODEL', Program::class),
        'role' => env('FILAMENT_TEAM_MANAGEMENT_ROLE_MODEL', \Spatie\Permission\Models\Role::class),
    ],
];
