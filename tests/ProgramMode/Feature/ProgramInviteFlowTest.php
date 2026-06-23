<?php

use Illuminate\Support\Facades\Mail;
use Stats4sd\FilamentTeamManagement\Mail\InviteUser;
use Stats4sd\FilamentTeamManagement\Mail\UpdateUser;
use Stats4sd\FilamentTeamManagement\Models\Invite;
use Stats4sd\FilamentTeamManagement\Models\Program;
use Stats4sd\FilamentTeamManagement\Models\User;

beforeEach(function () {
    Mail::fake();
    $this->inviter = actingAsAdmin();
});

it('creates a pending Program Admin invite for an unknown email', function () {
    $program = Program::factory()->create();
    $programAdminRole = config('permission.models.role')::findByName('Program Admin', 'web');

    $program->sendInvites(['programmer@example.test']);

    $invite = Invite::withoutGlobalScope('onlyUnconfirmed')->where('email', 'programmer@example.test')->first();

    expect($invite)->not->toBeNull()
        ->and($invite->is_confirmed)->toBeFalse()
        ->and($invite->program->is($program))->toBeTrue()
        ->and($invite->role_id)->toBe($programAdminRole->id);

    Mail::assertSent(InviteUser::class);
});

it('attaches a registered user to the program and mails an update', function () {
    $program = Program::factory()->create();
    $existing = User::factory()->create();

    $program->sendInvites([$existing->email]);

    expect($program->users()->whereKey($existing->id)->exists())->toBeTrue();

    Mail::assertSent(UpdateUser::class);
    Mail::assertNotSent(InviteUser::class);
});

it('does not duplicate program membership', function () {
    $program = Program::factory()->create();
    $existing = User::factory()->create();
    $program->users()->attach($existing);

    $program->sendInvites([$existing->email]);

    expect($program->users()->whereKey($existing->id)->count())->toBe(1);
    Mail::assertNothingSent();
});
