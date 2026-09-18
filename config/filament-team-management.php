<?php

// config for Stats4sd/FilamentTeamManagement
use Stats4sd\FilamentTeamManagement\Models\Program;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Models\User;

return [
    'use_programs' => env('FILAMENT_TEAM_MANAGEMENT_USE_PROGRAMS', false),

    'queue_mail' => env('FILAMENT_TEAM_MANAGEMENT_QUEUE_MAIL', true),
    'invite_expiry_days' => null,
    'participants' => [],
    'user_picker' => null,
    'no_memberships_route' => null,
    'panels' => ['app' => 'app', 'program' => 'program', 'admin' => 'admin'],

    'models' => [
        'user' => env('FILAMENT_TEAM_MANAGEMENT_USER_MODEL', User::class),
        'team' => env('FILAMENT_TEAM_MANAGEMENT_TEAM_MODEL', Team::class),
        'program' => env('FILAMENT_TEAM_MANAGEMENT_PROGRAM_MODEL', Program::class),
    ],

    // When using custom table names for your users or teams table, you can set them here
    'table_names' => [
        'invites' => env('FILAMENT_TEAM_MANAGEMENT_INVITES_TABLE', 'invites'),
        'users' => env('FILAMENT_TEAM_MANAGEMENT_USER_TABLE', 'users'),
        'teams' => env('FILAMENT_TEAM_MANAGEMENT_TEAMS_TABLE', 'teams'),
        'programs' => env('FILAMENT_TEAM_MANAGEMENT_PROGRAMS_TABLE', 'programs'),
        'program_members' => env('FILAMENT_TEAM_MANAGEMENT_PROGRAM_MEMBERS_TABLE', 'program_members'),
        'program_team' => env('FILAMENT_TEAM_MANAGEMENT_PROGRAM_TEAM_TABLE', 'program_team'),
        'team_members' => env('FILAMENT_TEAM_MANAGEMENT_TEAM_MEMBERS_TABLE', 'team_members'),
    ],

    // When using custom foreign keys for your users or teams table, you can set them here
    'column_names' => [
        'users_foreign_key' => env('FILAMENT_TEAM_MANAGEMENT_USER_FOREIGN_KEY', 'user_id'),
        'teams_foreign_key' => env('FILAMENT_TEAM_MANAGEMENT_TEAMS_FOREIGN_KEY', 'team_id'),
        'programs_foreign_key' => env('FILAMENT_TEAM_MANAGEMENT_PROGRAMS_FOREIGN_KEY', 'program_id'),
    ],

    // Lowercase singular display words shared by UI labels and email snapshots.
    // Plain config literals; these do not add environment settings or installer writes.
    'names' => [
        'user' => 'user',
        'team' => 'team',
        'program' => 'program',
    ],
];
