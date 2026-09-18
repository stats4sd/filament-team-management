<?php

namespace Stats4sd\FilamentTeamManagement\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

final class MembershipMail
{
    public static function dispatch(Model $target, string $email, Mailable $mail, ?InvitationResult $result = null): void
    {
        $queued = (bool) config('filament-team-management.queue_mail', true);
        if ($result) {
            $result->mailStatus = 'pending_commit';
        }
        $target->getConnection()->afterCommit(function () use ($email, $mail, $result, $queued): void {
            try {
                if ($queued) {
                    Mail::to($email)->queue($mail);
                } else {
                    Mail::to($email)->send($mail);
                }
                if ($result) {
                    $result->mailStatus = $queued ? 'queued' : 'sent';
                }
            } catch (\Throwable $exception) {
                if (! $result) {
                    throw new InvitationDeliveryFailed;
                }
                $result->mailStatus = 'failed';
                $result->error = 'The membership change was saved, but its email could not be dispatched.';
            }
        });
    }

    public static function snapshot(Model $target, ?Model $sender): array
    {
        return [
            'targetName' => (string) $target->getAttribute('name'),
            'targetLabel' => (string) config('filament-team-management.names.' . Membership::type($target)),
            'senderName' => (string) ($sender?->getAttribute('name') ?? 'A site administrator'),
            'senderEmail' => (string) ($sender?->getAttribute('email') ?? ''),
        ];
    }
}
