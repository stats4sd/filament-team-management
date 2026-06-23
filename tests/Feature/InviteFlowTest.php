<?php

use Illuminate\Support\Facades\Mail;
use Stats4sd\FilamentTeamManagement\Mail\InviteUser;
use Stats4sd\FilamentTeamManagement\Mail\UpdateUser;
use Stats4sd\FilamentTeamManagement\Models\Invite;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Models\User;

beforeEach(function () {
    Mail::fake();
    $this->inviter = actingAsAdmin();
});

describe('Team::sendInvites', function () {
    it('creates a pending invite and mails an unknown email', function () {
        $team = Team::factory()->create();

        $team->sendInvites(['newcomer@example.test']);

        $invite = Invite::withoutGlobalScope('onlyUnconfirmed')->where('email', 'newcomer@example.test')->first();

        expect($invite)->not->toBeNull()
            ->and($invite->is_confirmed)->toBeFalse()
            ->and($invite->team->is($team))->toBeTrue()
            ->and($invite->inviter_id)->toBe($this->inviter->id);

        Mail::assertSent(InviteUser::class);
    });

    it('attaches a registered user directly and mails an update', function () {
        $team = Team::factory()->create();
        $existing = User::factory()->create();

        $team->sendInvites([$existing->email]);

        expect($team->members()->whereKey($existing->id)->exists())->toBeTrue();

        $trace = Invite::withoutGlobalScope('onlyUnconfirmed')->where('email', $existing->email)->first();
        expect($trace->is_confirmed)->toBeTrue();

        Mail::assertSent(UpdateUser::class);
        Mail::assertNotSent(InviteUser::class);
    });

    it('does not duplicate membership for a user already on the team', function () {
        $team = Team::factory()->create();
        $existing = User::factory()->create();
        $team->members()->attach($existing);

        $team->sendInvites([$existing->email]);

        expect($team->members()->whereKey($existing->id)->count())->toBe(1);
        Mail::assertNothingQueued();
        Mail::assertNothingSent();
    });

    it('skips empty email entries', function () {
        $team = Team::factory()->create();

        $team->sendInvites(['', null]);

        expect(Invite::withoutGlobalScope('onlyUnconfirmed')->count())->toBe(0);
        Mail::assertNothingSent();
    });
});

describe('User::sendInvites', function () {
    it('creates a role invite for an unknown email', function () {
        $roleId = config('permission.models.role')::findByName('Super Admin', 'web')->id;

        $this->inviter->sendInvites([
            ['email' => 'rolee@example.test', 'role' => $roleId],
        ]);

        $invite = Invite::withoutGlobalScope('onlyUnconfirmed')->where('email', 'rolee@example.test')->first();

        expect($invite)->not->toBeNull()
            ->and($invite->role_id)->toBe($roleId);

        Mail::assertSent(InviteUser::class);
    });

    it('assigns the role directly to an already-registered user', function () {
        $role = config('permission.models.role')::findByName('Super Admin', 'web');
        $existing = User::factory()->create();

        $this->inviter->sendInvites([
            ['email' => $existing->email, 'role' => $role->id],
        ]);

        expect($existing->fresh()->hasRole('Super Admin'))->toBeTrue();
    });
});
