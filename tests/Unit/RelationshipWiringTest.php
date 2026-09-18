<?php

use Stats4sd\FilamentTeamManagement\Models\Program;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\HostUser as User;

it('returns all direct memberships from both relation aliases', function () {
    $team = Team::factory()->create();
    $admin = User::factory()->create();
    $member = User::factory()->create();

    $team->users()->attach($admin);
    $team->users()->attach($member);

    expect($team->users()->count())->toBe(2)
        ->and($team->members->pluck('id')->all())->toBe([$admin->id, $member->id]);
});

it('aliases members() to users() on Program', function () {
    withPrograms();

    $program = Program::factory()->create();
    $user = User::factory()->create();

    $program->users()->attach($user);

    expect($program->members->pluck('id')->all())->toBe([$user->id])
        ->and($program->members()->getTable())->toBe($program->users()->getTable());
});
