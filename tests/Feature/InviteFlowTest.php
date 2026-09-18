<?php

use Illuminate\Support\Facades\Mail;
use Stats4sd\FilamentTeamManagement\Actions\SendMembershipInvitation;
use Stats4sd\FilamentTeamManagement\Mail\InviteUser;
use Stats4sd\FilamentTeamManagement\Mail\UpdateUser;
use Stats4sd\FilamentTeamManagement\Models\Invite;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\HostUser as User;

beforeEach(function () {
    Mail::fake();
    $this->inviter = actingAsAdmin();
});

describe('Team membership invitations', function () {
    it('creates a pending invite and mails an unknown email', function () {
        $team = Team::factory()->create();

        app(SendMembershipInvitation::class)->handle($this->inviter, $team, 'newcomer@example.test');

        $invite = Invite::query()->where('email', 'newcomer@example.test')->first();

        expect($invite)->not->toBeNull()
            ->and($invite->is_confirmed)->toBeFalse()
            ->and($invite->team->is($team))->toBeTrue()
            ->and($invite->inviter_id)->toBe($this->inviter->id);

        Mail::assertQueued(InviteUser::class);
    });

    it('attaches a registered user directly and mails an update', function () {
        $team = Team::factory()->create();
        $existing = User::factory()->create();

        app(SendMembershipInvitation::class)->handle($this->inviter, $team, $existing->email);

        expect($team->members()->whereKey($existing->id)->exists())->toBeTrue();

        $trace = Invite::query()->where('email', $existing->email)->first();
        expect($trace)->toBeNull();

        Mail::assertQueued(UpdateUser::class);
        Mail::assertNotQueued(InviteUser::class);
    });

    it('does not duplicate membership for a user already on the team', function () {
        $team = Team::factory()->create();
        $existing = User::factory()->create();
        $team->members()->attach($existing);

        app(SendMembershipInvitation::class)->handle($this->inviter, $team, $existing->email);

        expect($team->members()->whereKey($existing->id)->count())->toBe(1);
        Mail::assertNothingQueued();
        Mail::assertNothingSent();
    });

    it('skips empty email entries', function () {
        $team = Team::factory()->create();

        app(SendMembershipInvitation::class)->handle($this->inviter, $team, '');
        app(SendMembershipInvitation::class)->handle($this->inviter, $team, null);

        expect(Invite::query()->count())->toBe(0);
        Mail::assertNothingSent();
    });
});
