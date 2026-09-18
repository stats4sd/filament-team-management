<?php

namespace Stats4sd\FilamentTeamManagement\Actions;

use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Stats4sd\FilamentTeamManagement\Events\InvitationAccepted;
use Stats4sd\FilamentTeamManagement\Events\MemberAdded;
use Stats4sd\FilamentTeamManagement\Events\RegisteredWithData;
use Stats4sd\FilamentTeamManagement\Models\Invite;
use Stats4sd\FilamentTeamManagement\Support\Membership;
use Stats4sd\FilamentTeamManagement\Support\MembershipContext;

final class AcceptMembershipInvitation
{
    public function handle(string $token, array $data): Model
    {
        Validator::make($data, ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255'], 'password' => ['required', 'string', 'min:10']])->validate();
        $candidate = Invite::query()->withoutGlobalScopes()->where('token', $token)->first();
        if (! $candidate || ! $candidate->isPending()) {
            Membership::invalid('This invitation is invalid, expired or already accepted. Contact the person who invited you.');
        }
        $target = $candidate->target();
        $class = config('filament-team-management.models.user');
        Membership::sameConnection($candidate, $target, new $class);

        return $candidate->getConnection()->transaction(function () use ($token, $data, $candidate, $target, $class): Model {
            // Acceptance creates a new, transaction-private user; no existing-user lock is taken late.
            $target = Membership::lock([$target])[Membership::key($target)];
            $invite = Invite::query()->withoutGlobalScopes()->whereKey($candidate->getKey())->lockForUpdate()->first();
            if (! $invite || ! hash_equals($invite->token, $token) || ! $invite->isPending()) {
                Membership::invalid('This invitation is invalid, expired or already accepted. Contact the person who invited you.');
            }
            Membership::assertInviteTarget($invite, $target);
            $email = Membership::email($invite->email);
            if (Membership::email($data['email']) !== $email) {
                Membership::invalid('Register with the email address that received this invitation.');
            }
            if ($class::query()->withoutGlobalScopes()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
                Membership::invalid('An account already exists for this email address. Sign in and ask the inviter to add your existing account.');
            }
            // Membership/target identifiers and client-supplied privileged attributes are never account input.
            $registration = ['name' => $data['name'], 'email' => $email, 'password' => Hash::make($data['password'])];

            try {
                $user = new $class($registration);
                if (! $user->save()) {
                    Membership::invalid('The account creation was cancelled. The invitation has not been accepted.');
                }
            } catch (QueryException $exception) {
                if (in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                    Membership::invalid('An account may already exist for this email address. Sign in or contact the inviter.');
                }

                throw $exception;
            }
            $context = new MembershipContext('accept_invitation', null, $target, $user, $invite, origin: 'invitation_acceptance', acceptance: true);
            Membership::participant('before', $context);
            $target->users()->attach($user->getKey());
            Membership::assertAttachment($target->users(), $user, true);
            $invite->forceFill(['is_confirmed' => true]);
            if (! $invite->save()) {
                Membership::invalid('The invitation acceptance was cancelled. No account or membership was created.');
            }
            $context->changed = true;
            Membership::participant('after', $context);
            Membership::observe($context, MemberAdded::class);
            Membership::observe($context, InvitationAccepted::class);
            $eventData = [...$data, ...$registration, 'original_password' => $data['password']];
            $target->getConnection()->afterCommit(function () use ($user, $eventData): void {
                event(new Registered($user));
                event(new RegisteredWithData($user, $eventData));
            });

            return $user;
        }, 1);
    }
}
