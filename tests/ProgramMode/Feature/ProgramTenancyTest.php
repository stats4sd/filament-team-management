<?php

use Filament\Facades\Filament;
use Stats4sd\FilamentTeamManagement\Models\Program;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Models\User;

beforeEach(function () {
    $this->programPanel = Filament::getPanel('program');
});

it('returns only the programs a user belongs to', function () {
    $user = User::factory()->create();
    $mine = Program::factory()->create();
    Program::factory()->create();
    $user->programs()->attach($mine);

    expect($user->getTenants($this->programPanel)->pluck('id')->all())->toBe([$mine->id]);
});

it('returns all programs for a holder of view all programs', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view all programs');
    Program::factory()->count(3)->create();

    expect($user->getTenants($this->programPanel)->count())->toBe(3);
});

it('grants program tenant access to members and view-all holders only', function () {
    $member = User::factory()->create();
    $stranger = User::factory()->create();
    $superuser = User::factory()->create();
    $superuser->givePermissionTo('view all programs');

    $program = Program::factory()->create();
    $member->programs()->attach($program);

    expect($member->canAccessTenant($program))->toBeTrue()
        ->and($stranger->canAccessTenant($program))->toBeFalse()
        ->and($superuser->canAccessTenant($program))->toBeTrue();
});

it('unions direct teams with program-reachable teams in getAllAccessibleTeams', function () {
    $user = User::factory()->create();

    $directTeam = Team::factory()->create();
    $user->teams()->attach($directTeam);

    $program = Program::factory()->create();
    $programTeam = Team::factory()->create();
    $program->teams()->attach($programTeam);
    $user->programs()->attach($program);

    $accessibleIds = $user->getAllAccessibleTeams()->pluck('id')->sort()->values()->all();

    expect($accessibleIds)->toBe(collect([$directTeam->id, $programTeam->id])->sort()->values()->all());
});
