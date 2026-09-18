<?php

use Filament\Facades\Filament;
use Stats4sd\FilamentTeamManagement\Models\Program;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\HostUser as User;

it('denies panel and tenant access in the unconfigured package base', function () {
    $user = new Stats4sd\FilamentTeamManagement\Models\User;
    expect($user->canAccessPanel(Filament::getPanel('app')))->toBeFalse()->and($user->getTenants(Filament::getPanel('app')))->toBeEmpty()->and(method_exists($user, 'isAdmin'))->toBeFalse();
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
