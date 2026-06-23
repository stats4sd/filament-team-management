<?php

use Stats4sd\FilamentTeamManagement\Models\Program;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Models\User;

it('splits team members and admins by the is_admin pivot column', function () {
    $team = Team::factory()->create();
    $admin = User::factory()->create();
    $member = User::factory()->create();

    $team->users()->attach($admin, ['is_admin' => 1]);
    $team->users()->attach($member, ['is_admin' => 0]);

    expect($team->users()->count())->toBe(2)
        ->and($team->admins->pluck('id')->all())->toBe([$admin->id])
        ->and($team->members->pluck('id')->all())->toBe([$member->id]);
});

it('aliases members() to users() on Program', function () {
    withPrograms();

    $program = Program::factory()->create();
    $user = User::factory()->create();

    $program->users()->attach($user);

    expect($program->members->pluck('id')->all())->toBe([$user->id])
        ->and($program->members()->getTable())->toBe($program->users()->getTable());
});
