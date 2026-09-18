<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Stats4sd\FilamentTeamManagement\Actions\SendMembershipInvitation;
use Stats4sd\FilamentTeamManagement\Mail\InviteUser;
use Stats4sd\FilamentTeamManagement\Mail\UpdateUser;
use Stats4sd\FilamentTeamManagement\Models\Invite;
use Stats4sd\FilamentTeamManagement\Models\Program;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\HostUser as User;

beforeEach(function () {
    Mail::fake();
    $this->inviter = actingAsAdmin();
});

it('creates a pending program membership invite for an unknown email', function () {
    $program = Program::factory()->create();

    app(SendMembershipInvitation::class)->handle($this->inviter, $program, 'programmer@example.test');

    $invite = Invite::query()->where('email', 'programmer@example.test')->first();

    expect($invite)->not->toBeNull()
        ->and($invite->is_confirmed)->toBeFalse()
        ->and($invite->program->is($program))->toBeTrue();

    Mail::assertQueued(InviteUser::class);
});

it('attaches a registered user to the program and mails an update', function () {
    $program = Program::factory()->create();
    $existing = User::factory()->create();

    app(SendMembershipInvitation::class)->handle($this->inviter, $program, $existing->email);

    expect($program->users()->whereKey($existing->id)->exists())->toBeTrue();

    Mail::assertQueued(UpdateUser::class);
    Mail::assertNotQueued(InviteUser::class);
});

it('does not duplicate program membership', function () {
    $program = Program::factory()->create();
    $existing = User::factory()->create();
    $program->users()->attach($existing);

    app(SendMembershipInvitation::class)->handle($this->inviter, $program, $existing->email);

    expect($program->users()->whereKey($existing->id)->count())->toBe(1);
    Mail::assertNothingSent();
});

it('creates membership invitations without a permission backend', function () {
    expect(Schema::hasTable('roles'))->toBeFalse();
    $program = Program::factory()->create();
    $result = app(SendMembershipInvitation::class)->handle($this->inviter, $program, 'independent@example.test');
    expect($result->status)->toBe('invitation_created')->and($result->invite->program->is($program))->toBeTrue();
    Mail::assertQueued(InviteUser::class);
});
