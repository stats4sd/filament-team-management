<?php

// config for Stats4sd/FilamentTeamManagement
return [
    'use_programs' => env('FILAMENT_TEAM_MANAGEMENT_USE_PROGRAMS', false),

    'models' => [
        'user' => env('FILAMENT_TEAM_MANAGEMENT_USER_MODEL', \Stats4sd\FilamentTeamManagement\Models\User::class),
        'team' => env('FILAMENT_TEAM_MANAGEMENT_TEAM_MODEL', \Stats4sd\FilamentTeamManagement\Models\Team::class),
        'program' => env('FILAMENT_TEAM_MANAGEMENT_PROGRAM_MODEL', \Stats4sd\FilamentTeamManagement\Models\Program::class),
    ],
];
