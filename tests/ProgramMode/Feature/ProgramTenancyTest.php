<?php

use Filament\Facades\Filament;
use Stats4sd\FilamentTeamManagement\Models\Program;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\HostUser as User;

beforeEach(function () {
    $this->programPanel = Filament::getPanel('program');
    Filament::setCurrentPanel($this->programPanel);
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
    $user->forceFill(['host_admin' => true])->save();
    Program::factory()->count(3)->create();

    expect($user->getTenants($this->programPanel)->count())->toBe(3);
});

it('grants program tenant access to members and view-all holders only', function () {
    $member = User::factory()->create();
    $stranger = User::factory()->create();
    $superuser = User::factory()->create();
    $superuser->forceFill(['host_admin' => true])->save();

    $program = Program::factory()->create();
    $member->programs()->attach($program);

    expect($member->canAccessTenant($program))->toBeTrue()
        ->and($stranger->canAccessTenant($program))->toBeFalse()
        ->and($superuser->canAccessTenant($program))->toBeTrue();
});

it('does not infer team access from a program membership', function () {
    $user = User::factory()->create();

    $directTeam = Team::factory()->create();
    $user->teams()->attach($directTeam);

    $program = Program::factory()->create();
    $programTeam = Team::factory()->create();
    $program->teams()->attach($programTeam);
    $user->programs()->attach($program);

    $accessibleIds = $user->getTenants(Filament::getPanel('app'))->pluck('id')->sort()->values()->all();

    expect($accessibleIds)->toBe(collect([$directTeam->id])->sort()->values()->all());
});
