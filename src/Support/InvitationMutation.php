<?php

namespace Stats4sd\FilamentTeamManagement\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Stats4sd\FilamentTeamManagement\Events\InvitationCancelled;
use Stats4sd\FilamentTeamManagement\Events\InvitationResent;
use Stats4sd\FilamentTeamManagement\Mail\InviteUser;
use Stats4sd\FilamentTeamManagement\Models\Invite;

final class InvitationMutation
{
    public function run(Authenticatable $actor, Model $target, Invite $invite, bool $resend): Invite | bool
    {
        $actorModel = Membership::actor($actor);
        Membership::type($target);
        Membership::sameConnection($actorModel, $target, $invite);

        return $target->getConnection()->transaction(function () use ($actorModel, $target, $invite, $resend): Invite | bool {
            $actor = Membership::lock([$actorModel])[Membership::key($actorModel)];
            $target = Membership::lock([$target])[Membership::key($target)];
            $locked = $invite->newQueryWithoutScopes()->whereKey($invite->getKey())->lockForUpdate()->first();
            if (! $locked) {
                Membership::invalid('This invitation is no longer available.');
            }
            Membership::assertInviteTarget($locked, $target);
            Membership::authorize($actor, $resend ? 'resendInvitation' : 'cancelInvitation', $target, $locked);
            if ($locked->isAccepted() || ! hash_equals($locked->token, $invite->token)) {
                Membership::invalid('This invitation has already been accepted or changed. Refresh the invitation list.');
            }
            $context = new MembershipContext($resend ? 'resend_invitation' : 'cancel_invitation', $actor, $target, invite: $locked);
            Membership::participant('before', $context);
            if ($resend) {
                $locked->forceFill(['token' => Str::random(64), 'expires_at' => Membership::expiry()]);
                if (! $locked->save()) {
                    Membership::invalid('The invitation renewal was cancelled. No changes were saved.');
                }
            } else {
                if (! $locked->delete()) {
                    Membership::invalid('The invitation cancellation was rejected. No changes were saved.');
                }
            }
            $context->changed = true;
            Membership::participant('after', $context);
            Membership::observe($context, $resend ? InvitationResent::class : InvitationCancelled::class);
            if ($resend) {
                MembershipMail::dispatch($target, $locked->email, new InviteUser($locked));
            }

            return $resend ? $locked : true;
        }, 1);
    }
}
