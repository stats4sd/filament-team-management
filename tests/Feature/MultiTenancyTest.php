<?php

use Filament\Facades\Filament;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Models\User;

beforeEach(function () {
    $this->appPanel = Filament::getPanel('app');
});

it('returns only the teams a user belongs to', function () {
    $user = User::factory()->create();
    $mine = Team::factory()->create();
    Team::factory()->create();
    $user->teams()->attach($mine);

    $tenants = $user->getTenants($this->appPanel);

    expect($tenants->pluck('id')->all())->toBe([$mine->id]);
});

it('returns all teams for a user with the view all teams permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view all teams');
    Team::factory()->count(3)->create();

    expect($user->getTenants($this->appPanel)->count())->toBe(3);
});

it('grants tenant access to members and to holders of view all teams', function () {
    $member = User::factory()->create();
    $stranger = User::factory()->create();
    $superuser = User::factory()->create();
    $superuser->givePermissionTo('view all teams');

    $team = Team::factory()->create();
    $member->teams()->attach($team);

    expect($member->canAccessTenant($team))->toBeTrue()
        ->and($stranger->canAccessTenant($team))->toBeFalse()
        ->and($superuser->canAccessTenant($team))->toBeTrue();
});

it('falls back to the first accessible team when no latest team is set', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);

    Filament::setCurrentPanel($this->appPanel);

    expect($user->getDefaultTenant($this->appPanel)->is($team))->toBeTrue();
});

it('prefers the latest team as the default tenant', function () {
    $user = User::factory()->create();
    $a = Team::factory()->create();
    $b = Team::factory()->create();
    $user->teams()->attach([$a->id, $b->id]);
    $user->latestTeam()->associate($b)->save();

    Filament::setCurrentPanel($this->appPanel);

    expect($user->getDefaultTenant($this->appPanel)->is($b))->toBeTrue();
});

it('returns just the direct teams from getAllAccessibleTeams when programs are off', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);

    expect($user->getAllAccessibleTeams()->pluck('id')->all())->toBe([$team->id]);
});
