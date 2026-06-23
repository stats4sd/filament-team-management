<?php

use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Models\User;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\CustomUser;

it('builds the team members relationship from the configured table and keys', function () {
    $relation = (new Team)->users();

    expect($relation->getTable())->toBe(config('filament-team-management.table_names.team_members'))
        ->and($relation->getForeignPivotKeyName())->toBe(config('filament-team-management.column_names.teams_foreign_key'))
        ->and($relation->getRelatedPivotKeyName())->toBe(config('filament-team-management.column_names.users_foreign_key'))
        ->and($relation->getRelated())->toBeInstanceOf(User::class);
});

it('honours custom table and foreign-key names set in config', function () {
    config()->set('filament-team-management.table_names.team_members', 'custom_team_members');
    config()->set('filament-team-management.column_names.teams_foreign_key', 'custom_team_id');
    config()->set('filament-team-management.column_names.users_foreign_key', 'custom_user_id');

    $relation = (new Team)->users();

    expect($relation->getTable())->toBe('custom_team_members')
        ->and($relation->getForeignPivotKeyName())->toBe('custom_team_id')
        ->and($relation->getRelatedPivotKeyName())->toBe('custom_user_id');
});

it('resolves the related model class through config', function () {
    config()->set('filament-team-management.models.user', CustomUser::class);

    expect((new Team)->users()->getRelated())->toBeInstanceOf(CustomUser::class);
});
