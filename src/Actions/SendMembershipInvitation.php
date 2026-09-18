<?php

namespace Stats4sd\FilamentTeamManagement\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Stats4sd\FilamentTeamManagement\Events\InvitationCreated;
use Stats4sd\FilamentTeamManagement\Mail\InviteUser;
use Stats4sd\FilamentTeamManagement\Mail\UpdateUser;
use Stats4sd\FilamentTeamManagement\Models\Invite;
use Stats4sd\FilamentTeamManagement\Support\InvitationResult;
use Stats4sd\FilamentTeamManagement\Support\Membership;
use Stats4sd\FilamentTeamManagement\Support\MembershipContext;
use Stats4sd\FilamentTeamManagement\Support\MembershipMail;
use Stats4sd\FilamentTeamManagement\Support\MembershipMutation;

final class SendMembershipInvitation
{
    public function handle(Authenticatable $actor, Model $target, ?string $email): InvitationResult
    {
        $actorModel = Membership::actor($actor);
        Membership::type($target);
        Membership::sameConnection($actorModel, $target, new Invite);
        $email = Membership::email($email);
        // Even a blank submission must not bypass the management boundary.
        Membership::authorize($actor, 'inviteMember', $target);
        if ($email === '') {
            return new InvitationResult('skipped_blank', $email);
        }
        Validator::make(['email' => $email], ['email' => ['required', 'email', 'max:255']])->validate();

        return $target->getConnection()->transaction(function () use ($actorModel, $target, $email): InvitationResult {
            $class = config('filament-team-management.models.user');
            $user = $class::query()->withoutGlobalScopes()->whereRaw('LOWER(email) = ?', [$email])->first();
            $lockedUsers = Membership::lock($user ? [$actorModel, $user] : [$actorModel]);
            $actor = $lockedUsers[Membership::key($actorModel)];
            $user = $user ? $lockedUsers[Membership::key($user)] : null;
            $target = Membership::lock([$target])[Membership::key($target)];
            Membership::authorize($actor, 'inviteMember', $target);
            // Do not acquire a newly appearing account lock after the target lock.
            $current = $class::query()->withoutGlobalScopes()->whereRaw('LOWER(email) = ?', [$email])->first();
            if ($current?->getKey() !== $user?->getKey()) {
                Membership::invalid('The account changed while this invitation was starting. Please try again.');
            }
            if ($user) {
                Membership::authorize($actor, 'addMember', $target, $user);
                $changed = app(MembershipMutation::class)->run($actor, [['add_member', $target, $user, ['origin' => 'email_invite']]])[0];
                $result = new InvitationResult($changed ? 'member_added' : 'already_member', $email, user: $user);
                if ($changed) {
                    MembershipMail::dispatch($target, $email, new UpdateUser(MembershipMail::snapshot($target, $actor)), $result);
                }

                return $result;
            }
            $pending = $target->invites()->withoutGlobalScopes()->unaccepted()->where('email', $email)->lockForUpdate()->first();
            if ($pending) {
                return new InvitationResult($pending->isExpired() ? 'expired_pending' : 'duplicate_pending', $email, $pending);
            }
            $invite = $target->invites()->make([
                'email' => $email,
                'inviter_id' => $actor->getAuthIdentifier(),
                'token' => Str::random(64),
                'is_confirmed' => false,
                'expires_at' => Membership::expiry(),
            ]);
            $context = new MembershipContext('invite_member', $actor, $target, invite: $invite, origin: 'email_invite');
            Membership::participant('before', $context);
            if (! $invite->save()) {
                Membership::invalid('The invitation creation was cancelled. No invitation was saved.');
            }
            $context->changed = true;
            Membership::participant('after', $context);
            Membership::observe($context, InvitationCreated::class);
            $result = new InvitationResult('invitation_created', $email, $invite);
            MembershipMail::dispatch($target, $email, new InviteUser($invite), $result);

            return $result;
        }, 1);
    }
}
