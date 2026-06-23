<?php

use Stats4sd\FilamentTeamManagement\Models\Program;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Models\User;

it('reports isAdmin() only for users with the Super Admin role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    expect($admin->isAdmin())->toBeTrue()
        ->and(User::factory()->create()->isAdmin())->toBeFalse();
});

it('reports belongsToTeam() against the user teams relationship', function () {
    $user = User::factory()->create();
    $member = $user->teams()->save(Team::factory()->make());
    $other = Team::factory()->create();

    expect($user->belongsToTeam($member))->toBeTrue()
        ->and($user->belongsToTeam($other))->toBeFalse();
});

it('reports belongsToProgram() against the user programs relationship', function () {
    withPrograms();

    $user = User::factory()->create();
    $member = Program::factory()->create();
    $other = Program::factory()->create();
    $user->programs()->attach($member);

    expect($user->belongsToProgram($member))->toBeTrue()
        ->and($user->belongsToProgram($other))->toBeFalse();
});
