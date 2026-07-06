<?php

use Stats4sd\FilamentTeamManagement\Models\Invite;
use Stats4sd\FilamentTeamManagement\Models\Program;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Models\User;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\CustomProgram;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\CustomRole;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\CustomUser;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\ProjectTeam;

// ---------------------------------------------------------------------------
// table_names.team_members + the team pivot foreign keys (baseline coverage)
// ---------------------------------------------------------------------------

it('builds the team members relationship from the configured table and keys', function () {
    $relation = (new Team)->users();

    expect($relation->getTable())->toBe(config('filament-team-management.table_names.team_members'))
        ->and($relation->getForeignPivotKeyName())->toBe(config('filament-team-management.column_names.teams_foreign_key'))
        ->and($relation->getRelatedPivotKeyName())->toBe(config('filament-team-management.column_names.users_foreign_key'))
        ->and($relation->getRelated())->toBeInstanceOf(User::class);
});

it('honours custom team_members table and pivot foreign-key names set in config', function () {
    config()->set('filament-team-management.table_names.team_members', 'custom_team_members');
    config()->set('filament-team-management.column_names.teams_foreign_key', 'custom_team_id');
    config()->set('filament-team-management.column_names.users_foreign_key', 'custom_user_id');

    $relation = (new Team)->users();

    expect($relation->getTable())->toBe('custom_team_members')
        ->and($relation->getForeignPivotKeyName())->toBe('custom_team_id')
        ->and($relation->getRelatedPivotKeyName())->toBe('custom_user_id');
});

// ---------------------------------------------------------------------------
// models.* — every relationship resolves its related class through config
// ---------------------------------------------------------------------------

it('resolves models.user on the team members relationship', function () {
    config()->set('filament-team-management.models.user', CustomUser::class);

    expect((new Team)->users()->getRelated())->toBeInstanceOf(CustomUser::class);
});

it('resolves models.team on the Invite team relation', function () {
    config()->set('filament-team-management.models.team', ProjectTeam::class);

    expect((new Invite)->team()->getRelated())->toBeInstanceOf(ProjectTeam::class);
});

it('resolves models.program on the Invite program relation', function () {
    config()->set('filament-team-management.models.program', CustomProgram::class);

    expect((new Invite)->program()->getRelated())->toBeInstanceOf(CustomProgram::class);
});

it('resolves models.role on the Invite role relation', function () {
    config()->set('filament-team-management.models.role', CustomRole::class);

    expect((new Invite)->role()->getRelated())->toBeInstanceOf(CustomRole::class);
});

// ---------------------------------------------------------------------------
// table_names.* — model tables and pivots resolve through config
// ---------------------------------------------------------------------------

it('resolves table_names.users on the User model table', function () {
    config()->set('filament-team-management.table_names.users', 'custom_users');

    expect((new User)->getTable())->toBe('custom_users');
});

it('resolves table_names.teams on the Team model table', function () {
    config()->set('filament-team-management.table_names.teams', 'custom_teams');

    expect((new Team)->getTable())->toBe('custom_teams');
});

it('resolves table_names.programs on the Program model table', function () {
    config()->set('filament-team-management.table_names.programs', 'custom_programs');

    expect((new Program)->getTable())->toBe('custom_programs');
});

it('resolves table_names.program_members on the Program members pivot', function () {
    config()->set('filament-team-management.table_names.program_members', 'custom_program_members');

    expect((new Program)->users()->getTable())->toBe('custom_program_members');
});

// This is the case that would have caught bug 4.3: Team::programs() / Program::teams()
// read a nonexistent config key (team_programs / program_teams) and silently fell back
// to Laravel's guessed pivot name, so an app that renamed the pivot queried the wrong table.
it('resolves table_names.program_team on both sides of the program<->team pivot (guards 4.3)', function () {
    config()->set('filament-team-management.table_names.program_team', 'custom_program_team');

    expect((new Team)->programs()->getTable())->toBe('custom_program_team')
        ->and((new Program)->teams()->getTable())->toBe('custom_program_team');
});

// ---------------------------------------------------------------------------
// column_names.* — foreign keys resolve through config
// ---------------------------------------------------------------------------

it('resolves column_names.teams_foreign_key on team pivots', function () {
    config()->set('filament-team-management.column_names.teams_foreign_key', 'custom_team_id');

    expect((new Team)->users()->getForeignPivotKeyName())->toBe('custom_team_id')
        ->and((new Program)->teams()->getRelatedPivotKeyName())->toBe('custom_team_id');
});

it('resolves column_names.users_foreign_key on team pivots', function () {
    config()->set('filament-team-management.column_names.users_foreign_key', 'custom_user_id');

    expect((new Team)->users()->getRelatedPivotKeyName())->toBe('custom_user_id');
});

it('resolves column_names.programs_foreign_key on program relations', function () {
    config()->set('filament-team-management.column_names.programs_foreign_key', 'custom_program_id');

    expect((new Invite)->program()->getForeignKeyName())->toBe('custom_program_id')
        ->and((new Program)->teams()->getForeignPivotKeyName())->toBe('custom_program_id')
        ->and((new Team)->programs()->getRelatedPivotKeyName())->toBe('custom_program_id');
});

// This is the case that would have caught bug 4.1: the config file mapped
// column_names.programs_foreign_key to the WRONG env var
// (FILAMENT_TEAM_MANAGEMENT_PROGRAM_MODEL, a class name) instead of
// FILAMENT_TEAM_MANAGEMENT_PROGRAMS_FOREIGN_KEY. Setting the correct env var and
// re-reading the raw config file proves the mapping is now honoured.
it('reads column_names.programs_foreign_key from FILAMENT_TEAM_MANAGEMENT_PROGRAMS_FOREIGN_KEY (guards 4.1)', function () {
    $key = 'FILAMENT_TEAM_MANAGEMENT_PROGRAMS_FOREIGN_KEY';

    putenv("{$key}=custom_program_fk");
    $_ENV[$key] = 'custom_program_fk';
    $_SERVER[$key] = 'custom_program_fk';

    try {
        $config = include dirname(__DIR__, 2) . '/config/filament-team-management.php';

        expect($config['column_names']['programs_foreign_key'])->toBe('custom_program_fk');
    } finally {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);
    }
});
