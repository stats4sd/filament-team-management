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

    // When using custom table names for your users or teams table, you can set them here
    'table_names' => [
        'users' => env('FILAMENT_TEAM_MANAGEMENT_USERS_TABLE', 'users'),
        'teams' => env('FILAMENT_TEAM_MANAGEMENT_TEAMS_TABLE', 'teams'),
        'programs' => env('FILAMENT_TEAM_MANAGEMENT_PROGRAMS_TABLE', 'programs'),
        'program_members' => env('FILAMENT_TEAM_MANAGEMENT_PROGRAM_MEMBERS_TABLE', 'program_members'),
        'program_team' => env('FILAMENT_TEAM_MANAGEMENT_PROGRAM_TEAM_TABLE', 'program_team'),
        'team_members' => env('FILAMENT_TEAM_MANAGEMENT_TEAM_MEMBERS_TABLE', 'team_members'),
    ],

    // When using custom foreign keys for your users or teams table, you can set them here
    'column_names' => [
        'users_foreign_key' => env('FILAMENT_TEAM_MANAGEMENT_USERS_FOREIGN_KEY', 'user_id'),
        'teams_foreign_key' => env('FILAMENT_TEAM_MANAGEMENT_TEAMS_FOREIGN_KEY', 'team_id'),
        'programs_foreign_key' => env('FILAMENT_TEAM_MANAGEMENT_PROGRAM_MODEL', 'program_id'),
    ],
];
