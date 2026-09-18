<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Stats4sd\FilamentTeamManagement\Actions\AcceptMembershipInvitation;
use Stats4sd\FilamentTeamManagement\Actions\AddMember;
use Stats4sd\FilamentTeamManagement\Actions\CancelMembershipInvitation;
use Stats4sd\FilamentTeamManagement\Actions\CreateTeam;
use Stats4sd\FilamentTeamManagement\Actions\DeleteProgram;
use Stats4sd\FilamentTeamManagement\Actions\DeleteTeam;
use Stats4sd\FilamentTeamManagement\Actions\DeleteUser;
use Stats4sd\FilamentTeamManagement\Actions\LeaveMembership;
use Stats4sd\FilamentTeamManagement\Actions\LinkTeamToProgram;
use Stats4sd\FilamentTeamManagement\Actions\MembershipBatch;
use Stats4sd\FilamentTeamManagement\Actions\RemoveMember;
use Stats4sd\FilamentTeamManagement\Actions\ResendMembershipInvitation;
use Stats4sd\FilamentTeamManagement\Actions\SendMembershipInvitation;
use Stats4sd\FilamentTeamManagement\Actions\UnlinkTeamFromProgram;
use Stats4sd\FilamentTeamManagement\Actions\UpdateTeam;
use Stats4sd\FilamentTeamManagement\Contracts\MembershipParticipant;
use Stats4sd\FilamentTeamManagement\Events\InvitationAccepted;
use Stats4sd\FilamentTeamManagement\Events\MemberAdded;
use Stats4sd\FilamentTeamManagement\Events\MemberRemoved;
use Stats4sd\FilamentTeamManagement\Events\RegisteredWithData;
use Stats4sd\FilamentTeamManagement\Events\TeamLinkedToProgram;
use Stats4sd\FilamentTeamManagement\Events\TeamUnlinkedFromProgram;
use Stats4sd\FilamentTeamManagement\Mail\InviteUser;
use Stats4sd\FilamentTeamManagement\Mail\UpdateUser;
use Stats4sd\FilamentTeamManagement\Models\Invite;
use Stats4sd\FilamentTeamManagement\Models\Program;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Support\MembershipContext;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\HostUser as User;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Policies\MembershipPolicy;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Policies\UserPolicy;

function registrationData(string $email): array
{
    return ['name' => 'New member', 'email' => $email, 'password' => 'long-password'];
}

class RejectMembershipChange implements MembershipParticipant
{
    public function before(MembershipContext $context): void {}

    public function after(MembershipContext $context): void
    {
        throw new RuntimeException('Host invariant rejected the change.');
    }
}

it('denies unconfigured and target-denied policies while allowing explicit scoped grants', function () {
    $actor = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create();
    expect(fn () => app(AddMember::class)->handle($actor, $team, $member))->toThrow(AuthorizationException::class);
    $actor->grant($team, 'addMember');
    expect(app(AddMember::class)->handle($actor, $team, $member))->toBeTrue();
    $other = Team::factory()->create();
    expect(fn () => app(AddMember::class)->handle($actor, $other, $member))->toThrow(AuthorizationException::class);
    Gate::policy(Team::class, (new class {})::class);
    expect(fn () => app(AddMember::class)->handle($actor, $team, $member))->toThrow(AuthorizationException::class);
});

it('emits once per actual membership transition and supports independent leave permission', function () {
    Event::fake([MemberAdded::class, MemberRemoved::class]);
    $actor = actingAsAdmin();
    $member = User::factory()->create();
    $team = Team::factory()->create();
    expect(app(AddMember::class)->handle($actor, $team, $member))->toBeTrue()
        ->and(app(AddMember::class)->handle($actor, $team, $member))->toBeFalse();
    Event::assertDispatchedTimes(MemberAdded::class, 1);
    expect(app(LeaveMembership::class)->handle($member, $team))->toBeTrue();
    Event::assertDispatchedTimes(MemberRemoved::class, 1);
    expect($team->members()->count())->toBe(0);
});

it('rolls back bootstrap and outer transactions with no observation effects', function () {
    Event::fake([MemberAdded::class]);
    $actor = actingAsAdmin();
    config()->set('filament-team-management.participants', [RejectMembershipChange::class]);
    expect(fn () => app(CreateTeam::class)->handle($actor, ['name' => 'Rejected']))->toThrow(RuntimeException::class);
    expect(Team::count())->toBe(0);
    Event::assertNotDispatched(MemberAdded::class);
    config()->set('filament-team-management.participants', []);
    DB::beginTransaction();
    $team = app(CreateTeam::class)->handle($actor, ['name' => 'Rolled back']);
    expect($team->users()->whereKey($actor->id)->exists())->toBeTrue();
    Event::assertNotDispatched(MemberAdded::class);
    DB::rollBack();
    expect(Team::count())->toBe(0);
    Event::assertNotDispatched(MemberAdded::class);
});

it('atomically rejects a denied record in a deletion batch', function () {
    Event::fake([MemberRemoved::class]);
    $actor = User::factory()->create();
    $first = Team::factory()->create();
    $second = Team::factory()->create();
    $first->users()->attach($actor);
    $actor->grant($first, 'delete');
    expect(fn () => app(MembershipBatch::class)->handle($actor, 'delete_team', [$first, $second]))->toThrow(AuthorizationException::class);
    expect(Team::count())->toBe(2)->and($first->users()->count())->toBe(1);
    Event::assertNotDispatched(MemberRemoved::class);
});

it('normalizes invitation identity and reports all duplicate lifecycle states', function () {
    Mail::fake();
    $actor = actingAsAdmin();
    $team = Team::factory()->create();
    $send = app(SendMembershipInvitation::class);
    expect($send->handle($actor, $team, ' ')->status)->toBe('skipped_blank');
    expect(fn () => $send->handle($actor, $team, 'invalid'))->toThrow(ValidationException::class);
    $result = $send->handle($actor, $team, ' MEMBER@EXAMPLE.COM ');
    expect($result->status)->toBe('invitation_created')->and($result->email)->toBe('member@example.com')->and($result->mailStatus)->toBe('queued');
    expect($send->handle($actor, $team, 'member@example.com')->status)->toBe('duplicate_pending');
    $result->invite->forceFill(['expires_at' => now()->subSecond()])->save();
    expect($send->handle($actor, $team, 'member@example.com')->status)->toBe('expired_pending');
    expect($team->invites()->pending()->count())->toBe(0)->and($team->invites()->count())->toBe(1);
    Mail::assertQueued(InviteUser::class, 1);
});

it('requires addMember as well as inviteMember for existing accounts without synthetic invitations', function () {
    Mail::fake();
    $actor = User::factory()->create();
    $member = User::factory()->create(['email' => 'member@example.com']);
    $team = Team::factory()->create();
    $actor->grant($team, 'inviteMember');
    expect(fn () => app(SendMembershipInvitation::class)->handle($actor, $team, $member->email))->toThrow(AuthorizationException::class);
    $actor->grant($team, 'addMember');
    expect(app(SendMembershipInvitation::class)->handle($actor, $team, $member->email)->status)->toBe('member_added');
    expect(app(SendMembershipInvitation::class)->handle($actor, $team, $member->email)->status)->toBe('already_member');
    expect(Invite::count())->toBe(0);
    Mail::assertQueued(UpdateUser::class, 1);
});

it('runs mail only after outer commit and honors synchronous delivery', function () {
    Mail::fake();
    $actor = actingAsAdmin();
    $team = Team::factory()->create();
    DB::beginTransaction();
    app(SendMembershipInvitation::class)->handle($actor, $team, 'rollback@example.com');
    Mail::assertNothingOutgoing();
    DB::rollBack();
    Mail::assertNothingOutgoing();
    config()->set('filament-team-management.queue_mail', false);
    expect(app(SendMembershipInvitation::class)->handle($actor, $team, 'sent@example.com')->mailStatus)->toBe('sent');
    Mail::assertSent(InviteUser::class, 1);
    Mail::assertNothingQueued();
});

it('renews expired invitations with immutable mail snapshots and rejects stale tokens', function () {
    Mail::fake();
    $actor = actingAsAdmin();
    $team = Team::factory()->create();
    $invite = app(SendMembershipInvitation::class)->handle($actor, $team, 'member@example.com')->invite;
    $oldToken = $invite->token;
    $mail = new InviteUser($invite);
    $invite->forceFill(['expires_at' => now()->subDay()])->save();
    $renewed = app(ResendMembershipInvitation::class)->handle($actor, $team, $invite);
    expect($renewed->token)->not->toBe($oldToken)->and($mail->acceptUrl)->toContain($oldToken)->and($renewed->isPending())->toBeTrue();
    expect(fn () => app(AcceptMembershipInvitation::class)->handle($oldToken, registrationData($invite->email)))->toThrow(ValidationException::class);
    expect(fn () => app(CancelMembershipInvitation::class)->handle($actor, $team, $invite))->toThrow(ValidationException::class);
    app(CancelMembershipInvitation::class)->handle($actor, $team, $renewed);
    expect(Invite::count())->toBe(0);
});

it('accepts the persisted capability atomically and preserves ordered credential integration separately', function () {
    Mail::fake();
    Event::fake([MemberAdded::class, InvitationAccepted::class, Registered::class, RegisteredWithData::class]);
    $actor = actingAsAdmin();
    $team = Team::factory()->create();
    $invite = app(SendMembershipInvitation::class)->handle($actor, $team, 'member@example.com')->invite;
    $user = app(AcceptMembershipInvitation::class)->handle($invite->token, registrationData($invite->email));
    expect($team->users()->whereKey($user->id)->exists())->toBeTrue()->and($invite->fresh()->isAccepted())->toBeTrue();
    Event::assertDispatched(RegisteredWithData::class, fn ($event) => $event->data['original_password'] === 'long-password' && $event->data['password'] !== 'long-password');
    Event::assertDispatched(InvitationAccepted::class, fn ($event) => $event->payload['actor_id'] === null && $event->payload['acceptance'] === true && ! str_contains(json_encode($event->payload), $invite->token));
    expect(fn () => app(AcceptMembershipInvitation::class)->handle($invite->token, registrationData($invite->email)))->toThrow(ValidationException::class);
    expect(User::where('email', $invite->email)->count())->toBe(1);
});

it('rejects tampered email, appeared accounts and failed acceptance participants without consumption', function () {
    Mail::fake();
    Event::fake([MemberAdded::class, InvitationAccepted::class, Registered::class]);
    $actor = actingAsAdmin();
    $team = Team::factory()->create();
    $invite = app(SendMembershipInvitation::class)->handle($actor, $team, 'member@example.com')->invite;
    expect(fn () => app(AcceptMembershipInvitation::class)->handle($invite->token, registrationData('other@example.com')))->toThrow(ValidationException::class);
    config()->set('filament-team-management.participants', [RejectMembershipChange::class]);
    expect(fn () => app(AcceptMembershipInvitation::class)->handle($invite->token, registrationData($invite->email)))->toThrow(RuntimeException::class);
    expect(User::where('email', $invite->email)->exists())->toBeFalse()->and($invite->fresh()->isPending())->toBeTrue();
    Event::assertNotDispatched(InvitationAccepted::class);
    Event::assertNotDispatched(Registered::class);
    config()->set('filament-team-management.participants', []);
    User::factory()->create(['email' => $invite->email]);
    expect(fn () => app(AcceptMembershipInvitation::class)->handle($invite->token, registrationData($invite->email)))->toThrow(ValidationException::class);
    expect($team->users()->count())->toBe(0)->and($invite->fresh()->isPending())->toBeTrue();
});

it('removes account memberships with usable after-commit removal payloads', function () {
    Event::fake([MemberRemoved::class]);
    $actor = actingAsAdmin();
    $member = User::factory()->create();
    $teams = Team::factory()->count(2)->create();
    foreach ($teams as $team) {
        app(AddMember::class)->handle($actor, $team, $member);
    }
    app(DeleteUser::class)->handle($actor, $member);
    expect(User::find($member->id))->toBeNull()->and(DB::table('team_members')->count())->toBe(0);
    Event::assertDispatchedTimes(MemberRemoved::class, 2);
    Event::assertDispatched(MemberRemoved::class, fn ($event) => $event->payload['user_id'] === $member->id && $event->payload['origin'] === 'account_deleted');
});

it('requires both target policies for program links and keeps associated teams on program deletion', function () {
    withPrograms();
    $actor = User::factory()->create();
    $program = Program::factory()->create();
    $team = Team::factory()->create();
    $actor->grant($program, 'linkTeam');
    expect(fn () => app(LinkTeamToProgram::class)->handle($actor, $program, $team))->toThrow(AuthorizationException::class);
    $actor->grant($team, 'linkProgram');
    expect(app(LinkTeamToProgram::class)->handle($actor, $program, $team))->toBeTrue();
    $admin = actingAsAdmin();
    app(DeleteProgram::class)->handle($admin, $program);
    expect(Team::find($team->id))->not->toBeNull()->and(DB::table('program_team')->count())->toBe(0);
});

it('reports transport failure as a saved invitation without claiming delivery', function () {
    Mail::shouldReceive('to')->once()->andReturnSelf();
    Mail::shouldReceive('queue')->once()->andThrow(new RuntimeException('Transport unavailable'));
    $actor = actingAsAdmin();
    $team = Team::factory()->create();
    $result = app(SendMembershipInvitation::class)->handle($actor, $team, 'failure@example.com');
    expect($result->status)->toBe('invitation_created')->and($result->mailStatus)->toBe('failed')->and($result->error)->toContain('saved')->and(Invite::count())->toBe(1);
});

it('retains safe sender snapshots and can resend after inviter deletion', function () {
    Mail::fake();
    $actor = actingAsAdmin();
    $team = Team::factory()->create();
    $invite = app(SendMembershipInvitation::class)->handle($actor, $team, 'orphan@example.com')->invite;
    $mail = new InviteUser($invite);
    $senderName = $actor->name;
    $manager = User::factory()->create(['host_admin' => true]);
    app(DeleteUser::class)->handle($manager, $actor);
    expect($invite->fresh()->inviter_id)->toBeNull()->and($mail->snapshot['senderName'])->toBe($senderName);
    $renewed = app(ResendMembershipInvitation::class)->handle($manager, $team, $invite->fresh());
    $renewedMail = new InviteUser($renewed);
    expect($renewedMail->snapshot['senderName'])->toBe('Someone')->and($renewedMail->render())->toContain('Someone');
});

class MembershipCustomTeam extends Team {}

it('uses configured subclasses tables and foreign keys for new and existing member invitations', function () {
    Mail::fake();
    $schema = Schema::getFacadeRoot();
    $schema->rename('teams', 'groups');
    $schema->rename('team_members', 'group_people');
    $schema->rename('invites', 'membership_requests');
    $schema->table('group_people', function ($table) {
        $table->renameColumn('team_id', 'group_ref');
        $table->renameColumn('user_id', 'person_ref');
    });
    $schema->table('membership_requests', fn ($table) => $table->renameColumn('team_id', 'group_ref'));
    config()->set('filament-team-management.models.team', MembershipCustomTeam::class);
    config()->set('filament-team-management.table_names.teams', 'groups');
    config()->set('filament-team-management.table_names.team_members', 'group_people');
    config()->set('filament-team-management.table_names.invites', 'membership_requests');
    config()->set('filament-team-management.column_names.teams_foreign_key', 'group_ref');
    config()->set('filament-team-management.column_names.users_foreign_key', 'person_ref');
    Gate::policy(MembershipCustomTeam::class, MembershipPolicy::class);
    $actor = actingAsAdmin();
    $team = MembershipCustomTeam::create(['name' => 'Custom group']);
    $invite = app(SendMembershipInvitation::class)->handle($actor, $team, 'new@example.com')->invite;
    expect($invite->getAttribute('group_ref'))->toBe($team->id)->and($invite->target())->toBeInstanceOf(MembershipCustomTeam::class);
    $user = app(AcceptMembershipInvitation::class)->handle($invite->token, registrationData($invite->email));
    expect($team->users()->whereKey($user->id)->exists())->toBeTrue();
    $member = User::factory()->create();
    expect(app(SendMembershipInvitation::class)->handle($actor, $team, $member->email)->status)->toBe('member_added')->and($team->users()->count())->toBe(2);
});

it('keeps accepted invitations as history and rejects resend cancellation and disabled programs', function () {
    Mail::fake();
    $actor = actingAsAdmin();
    $team = Team::factory()->create();
    $invite = app(SendMembershipInvitation::class)->handle($actor, $team, 'history@example.com')->invite;
    app(AcceptMembershipInvitation::class)->handle($invite->token, registrationData($invite->email));
    expect($team->invites()->accepted()->count())->toBe(1)->and($team->invites()->pending()->count())->toBe(0);
    expect(fn () => app(ResendMembershipInvitation::class)->handle($actor, $team, $invite->fresh()))->toThrow(ValidationException::class);
    expect(fn () => app(CancelMembershipInvitation::class)->handle($actor, $team, $invite->fresh()))->toThrow(ValidationException::class);
    expect(fn () => app(SendMembershipInvitation::class)->handle($actor, new Program, 'program@example.com'))->toThrow(ValidationException::class);
});

class IntegrateHostGrants implements MembershipParticipant
{
    public function before(MembershipContext $context): void {}

    public function after(MembershipContext $context): void
    {
        if ($context->operation === 'create_team') {
            $context->actor->grant($context->target);
        }
        if (in_array($context->operation, ['remove_member', 'leave'], true)) {
            DB::table('host_grants')->where('target_type', $context->target->getTable())->where('target_id', $context->target->getKey())->where('user_id', $context->user->getKey())->delete();
        }
    }
}

it('atomically integrates host bootstrap grants and revocation without restoring old grants on rejoin', function () {
    config()->set('filament-team-management.participants', [IntegrateHostGrants::class]);
    $actor = actingAsAdmin();
    $team = app(CreateTeam::class)->handle($actor, ['name' => 'Host governed']);
    expect(DB::table('host_grants')->where('user_id', $actor->id)->exists())->toBeTrue();
    $actor->forceFill(['host_admin' => false])->save();
    $member = User::factory()->create();
    app(AddMember::class)->handle($actor, $team, $member);
    $member->grant($team);
    config()->set('filament-team-management.participants', [IntegrateHostGrants::class, RejectMembershipChange::class]);
    expect(fn () => app(RemoveMember::class)->handle($actor, $team, $member))->toThrow(RuntimeException::class);
    expect($member->allowed($team, 'addMember'))->toBeTrue()->and($team->users()->whereKey($member->id)->exists())->toBeTrue();
    config()->set('filament-team-management.participants', [IntegrateHostGrants::class]);
    app(RemoveMember::class)->handle($actor, $team, $member);
    app(AddMember::class)->handle($actor, $team, $member);
    expect($member->allowed($team, 'addMember'))->toBeFalse()->and($team->users()->whereKey($member->id)->exists())->toBeTrue();
});

it('dispatches registration integrations in order only after the outer transaction commits', function () {
    Mail::fake();
    $seen = [];
    Event::listen(Registered::class, function () use (&$seen) {
        $seen[] = Registered::class;
    });
    Event::listen(RegisteredWithData::class, function () use (&$seen) {
        $seen[] = RegisteredWithData::class;
    });
    $actor = actingAsAdmin();
    $team = Team::factory()->create();
    $invite = app(SendMembershipInvitation::class)->handle($actor, $team, 'ordered@example.com')->invite;
    DB::beginTransaction();
    app(AcceptMembershipInvitation::class)->handle($invite->token, registrationData($invite->email));
    expect($seen)->toBe([]);
    DB::commit();
    expect($seen)->toBe([Registered::class, RegisteredWithData::class]);
});

class SimpleMembershipHostPolicy extends MembershipPolicy
{
    private function manages(User $actor, Model $target): bool
    {
        return $target->users()->whereKey($actor->getKey())->wherePivot('host_manager', true)->exists();
    }

    public function inviteMember(User $actor, Model $target): bool
    {
        return $this->manages($actor, $target);
    }

    public function addMember(User $actor, Model $target, Model $member): bool
    {
        return $this->manages($actor, $target);
    }

    public function removeMember(User $actor, Model $target, Model $member): bool
    {
        return $this->manages($actor, $target);
    }
}

it('supports a simple host-owned team manager flag without granting authority over another team', function () {
    Mail::fake();
    Schema::table('team_members', fn ($table) => $table->boolean('host_manager')->default(false));
    Gate::policy(Team::class, SimpleMembershipHostPolicy::class);
    $manager = User::factory()->create();
    $ordinary = User::factory()->create();
    $newMember = User::factory()->create();
    $first = Team::factory()->create();
    $second = Team::factory()->create();
    $first->users()->attach($manager, ['host_manager' => true]);
    $first->users()->attach($ordinary);
    expect(app(SendMembershipInvitation::class)->handle($manager, $first, $newMember->email)->status)->toBe('member_added');
    expect(fn () => app(SendMembershipInvitation::class)->handle($manager, $second, 'denied@example.com'))->toThrow(AuthorizationException::class);
    expect(fn () => app(AddMember::class)->handle($ordinary, $first, $newMember))->toThrow(AuthorizationException::class);
    expect(Gate::forUser($ordinary)->allows('viewMembers', $first))->toBeTrue()->and(Gate::forUser($ordinary)->allows('update', $first))->toBeFalse();
    expect(app(RemoveMember::class)->handle($manager, $first, $newMember))->toBeTrue();
    expect(app(LeaveMembership::class)->handle($ordinary, $first))->toBeTrue();
});

class SoftDeletedMembershipUser extends User
{
    use SoftDeletes;
}

it('removes memberships when a host soft-deletes an account through the shared action and rejects reattachment', function () {
    Schema::table('users', fn ($table) => $table->softDeletes());
    config()->set('filament-team-management.models.user', SoftDeletedMembershipUser::class);
    Gate::policy(SoftDeletedMembershipUser::class, UserPolicy::class);
    $actor = SoftDeletedMembershipUser::create(['name' => 'Host manager', 'email' => 'manager@example.com', 'password' => 'long-password', 'host_admin' => true]);
    $member = SoftDeletedMembershipUser::create(['name' => 'Member', 'email' => 'member@example.com', 'password' => 'long-password']);
    $team = Team::factory()->create();
    app(AddMember::class)->handle($actor, $team, $member);
    app(DeleteUser::class)->handle($actor, $member);
    expect(SoftDeletedMembershipUser::find($member->id))->toBeNull()
        ->and(SoftDeletedMembershipUser::withTrashed()->find($member->id)->trashed())->toBeTrue()
        ->and($team->users()->count())->toBe(0);
    expect(fn () => app(AddMember::class)->handle($actor, $team, $member))->toThrow(ModelNotFoundException::class);
});

it('rolls back memberships grants and events when a host model vetoes deletion', function () {
    Event::fake([MemberRemoved::class]);
    config()->set('filament-team-management.participants', [IntegrateHostGrants::class]);
    $actor = actingAsAdmin();
    $member = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($member);
    $member->grant($team);
    Team::deleting(fn () => false);
    expect(fn () => app(DeleteTeam::class)->handle($actor, $team))->toThrow(ValidationException::class);
    expect(Team::find($team->id))->not->toBeNull()->and($team->users()->count())->toBe(1)->and($member->allowed($team, 'addMember'))->toBeTrue();
    Event::assertNotDispatched(MemberRemoved::class);
});

it('rejects host observer cancellation during model creation or update', function (string $operation) {
    Event::fake([MemberAdded::class]);
    $actor = actingAsAdmin();
    $team = Team::factory()->create(['name' => 'Original']);
    Team::saving(fn () => false);
    $action = $operation === 'create' ? fn () => app(CreateTeam::class)->handle($actor, ['name' => 'Vetoed']) : fn () => app(UpdateTeam::class)->handle($actor, $team, ['name' => 'Vetoed']);
    expect($action)->toThrow(ValidationException::class);
    expect(Team::count())->toBe(1)->and($team->fresh()->name)->toBe('Original');
    Event::assertNotDispatched(MemberAdded::class);
})->with(['create', 'update']);

it('rolls back invitation workflows when a host vetoes persistence', function (string $operation) {
    Mail::fake();
    Event::fake([InvitationAccepted::class, Registered::class]);
    $actor = actingAsAdmin();
    $team = Team::factory()->create();
    $invite = Invite::factory()->forTeam($team)->create(['email' => 'veto@example.com']);
    if ($operation === 'cancel') {
        Invite::deleting(fn () => false);
    } elseif ($operation === 'account') {
        User::creating(fn () => false);
    } else {
        Invite::saving(fn () => false);
    }
    $action = match ($operation) {
        'create' => fn () => app(SendMembershipInvitation::class)->handle($actor, $team, 'other@example.com'),
        'resend' => fn () => app(ResendMembershipInvitation::class)->handle($actor, $team, $invite),
        'cancel' => fn () => app(CancelMembershipInvitation::class)->handle($actor, $team, $invite),
        default => fn () => app(AcceptMembershipInvitation::class)->handle($invite->token, registrationData($invite->email)),
    };
    expect($action)->toThrow(ValidationException::class);
    expect(Invite::count())->toBe(1)->and($invite->fresh()->isPending())->toBeTrue()->and($invite->fresh()->token)->toBe($invite->token)
        ->and(User::where('email', $invite->email)->exists())->toBeFalse()->and($team->users()->count())->toBe(0);
    Mail::assertNothingOutgoing();
    Event::assertNotDispatched(InvitationAccepted::class);
    Event::assertNotDispatched(Registered::class);
})->with(['create', 'resend', 'cancel', 'accept', 'account']);

it('cleans the full structural graph despite host visibility scopes', function () {
    Event::fake([MemberRemoved::class]);
    config()->set('filament-team-management.participants', [IntegrateHostGrants::class]);
    $actor = actingAsAdmin();
    $member = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($member);
    $member->grant($team);
    Team::addGlobalScope('hidden_from_current_view', fn ($query) => $query->whereRaw('1 = 0'));
    expect($member->teams()->count())->toBe(0);
    app(DeleteUser::class)->handle($actor, $member);
    expect(DB::table('team_members')->count())->toBe(0)->and(DB::table('host_grants')->count())->toBe(0);
    Event::assertDispatched(MemberRemoved::class, fn ($event) => $event->payload['target_id'] === $team->id && $event->payload['user_id'] === $member->id);
});

it('resolves structural invitation identity independently of host visibility scopes', function () {
    Mail::fake();
    $actor = actingAsAdmin();
    $member = User::factory()->create();
    $team = Team::factory()->create();
    User::addGlobalScope('hide_member', fn ($query) => $query->whereKeyNot($member->id));
    expect(app(SendMembershipInvitation::class)->handle($actor, $team, $member->email)->status)->toBe('member_added')->and(Invite::count())->toBe(0);
    Team::addGlobalScope('hide_target', fn ($query) => $query->whereRaw('1 = 0'));
    $invite = app(SendMembershipInvitation::class)->handle($actor, $team, 'hidden-target@example.com')->invite;
    $user = app(AcceptMembershipInvitation::class)->handle($invite->token, registrationData($invite->email));
    expect(DB::table('team_members')->where('user_id', $user->id)->where('team_id', $team->id)->exists())->toBeTrue();
});

it('uses leave authorization for self-removal through direct and bulk removal actions', function () {
    $actor = actingAsAdmin();
    $team = Team::factory()->create();
    $team->users()->attach($actor);
    Gate::policy(Team::class, (new class extends MembershipPolicy
    {
        public function leave(User $actor, Model $target): bool
        {
            return false;
        }
    })::class);
    expect(Gate::forUser($actor)->allows('removeMember', [$team, $actor]))->toBeTrue();
    expect(fn () => app(RemoveMember::class)->handle($actor, $team, $actor))->toThrow(AuthorizationException::class);
    expect(fn () => app(MembershipBatch::class)->handle($actor, 'remove_member', [$actor], $team))->toThrow(AuthorizationException::class);
    expect($team->users()->whereKey($actor->id)->exists())->toBeTrue();
});

class MembershipVetoPivot extends Pivot
{
    public static string $veto = '';

    protected static function booted(): void
    {
        static::creating(fn () => static::$veto !== 'create');
        static::deleting(fn () => static::$veto !== 'delete');
    }
}

class VetoPivotMembershipTeam extends Team
{
    public function users(): BelongsToMany
    {
        return parent::users()->using(MembershipVetoPivot::class);
    }
}

class VetoPivotMembershipProgram extends Program
{
    public function teams(): BelongsToMany
    {
        return parent::teams()->using(MembershipVetoPivot::class);
    }
}

it('rejects custom pivot vetoes without emitting membership changes or consuming invitations', function (string $operation) {
    Mail::fake();
    Event::fake([MemberAdded::class, MemberRemoved::class, InvitationAccepted::class]);
    config()->set('filament-team-management.models.team', VetoPivotMembershipTeam::class);
    Gate::policy(VetoPivotMembershipTeam::class, MembershipPolicy::class);
    $actor = actingAsAdmin();
    $member = User::factory()->create();
    $team = VetoPivotMembershipTeam::create(['name' => 'Custom pivot']);
    $invite = Invite::factory()->forTeam($team)->create(['email' => 'pivot-veto@example.com']);
    if ($operation === 'remove') {
        DB::table('team_members')->insert(['team_id' => $team->id, 'user_id' => $member->id]);
    }
    MembershipVetoPivot::$veto = $operation === 'remove' ? 'delete' : 'create';
    $action = match ($operation) {
        'add' => fn () => app(AddMember::class)->handle($actor, $team, $member),
        'remove' => fn () => app(RemoveMember::class)->handle($actor, $team, $member),
        'bootstrap' => fn () => app(CreateTeam::class)->handle($actor, ['name' => 'Rejected bootstrap']),
        default => fn () => app(AcceptMembershipInvitation::class)->handle($invite->token, registrationData($invite->email)),
    };
    expect($action)->toThrow(ValidationException::class);
    expect($team->users()->count())->toBe($operation === 'remove' ? 1 : 0)
        ->and(VetoPivotMembershipTeam::count())->toBe(1)->and($invite->fresh()->isPending())->toBeTrue()
        ->and(User::where('email', $invite->email)->exists())->toBeFalse();
    Event::assertNotDispatched(MemberAdded::class);
    Event::assertNotDispatched(MemberRemoved::class);
    Event::assertNotDispatched(InvitationAccepted::class);
    MembershipVetoPivot::$veto = '';
})->with(['add', 'remove', 'bootstrap', 'accept']);

it('rejects custom pivot vetoes during program team linking and unlinking', function (bool $unlink) {
    withPrograms();
    Event::fake([TeamLinkedToProgram::class, TeamUnlinkedFromProgram::class]);
    config()->set('filament-team-management.models.program', VetoPivotMembershipProgram::class);
    Gate::policy(VetoPivotMembershipProgram::class, MembershipPolicy::class);
    $actor = actingAsAdmin();
    $team = Team::factory()->create();
    $program = VetoPivotMembershipProgram::create(['name' => 'Custom link']);
    if ($unlink) {
        DB::table('program_team')->insert(['team_id' => $team->id, 'program_id' => $program->id]);
    }
    MembershipVetoPivot::$veto = $unlink ? 'delete' : 'create';
    $action = $unlink ? UnlinkTeamFromProgram::class : LinkTeamToProgram::class;
    expect(fn () => app($action)->handle($actor, $program, $team))->toThrow(ValidationException::class);
    expect($program->teams()->count())->toBe($unlink ? 1 : 0);
    Event::assertNotDispatched(TeamLinkedToProgram::class);
    Event::assertNotDispatched(TeamUnlinkedFromProgram::class);
    MembershipVetoPivot::$veto = '';
})->with([false, true]);

it('honors a deliberate host global Gate bypass without shipping one', function () {
    $actor = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create();
    expect(fn () => app(AddMember::class)->handle($actor, $team, $member))->toThrow(AuthorizationException::class);
    Gate::before(fn ($user) => $user->getAuthIdentifier() === $actor->getAuthIdentifier() ? true : null);
    expect(app(AddMember::class)->handle($actor, $team, $member))->toBeTrue();
});
