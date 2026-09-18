<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Support;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Actions\MembershipBatch;
use Stats4sd\FilamentTeamManagement\Actions\RemoveMember;
use Stats4sd\FilamentTeamManagement\Actions\SendMembershipInvitation;
use Stats4sd\FilamentTeamManagement\Support\Membership;
use Stats4sd\FilamentTeamManagement\Support\MembershipCandidates;

class MemberActions
{
    public static function invite(Closure $target): Action
    {
        return Action::make('invite')->label('Invite members')
            ->authorize(fn () => Access::allows('inviteMember', $target()))
            ->schema([Repeater::make('emails')->label('Email addresses')->simple(TextInput::make('email')->email()->required())->reorderable(false)->addActionLabel('Add another email address')])
            ->modalDescription(fn () => 'Invite members to this ' . config('filament-team-management.names.' . Membership::type($target())) . '. New users receive a registration invitation. Existing users are added immediately and receive a notification.')
            ->action(function (array $data) use ($target) {
                foreach ($data['emails'] as $email) {
                    $result = app(SendMembershipInvitation::class)->handle(Access::actor(), $target(), $email);
                    $message = match ($result->status) {
                        'invitation_created' => 'Invitation created', 'member_added' => 'Member added',
                        'duplicate_pending' => 'Invitation already pending', 'expired_pending' => 'Invitation expired — use Resend on the Invitations tab',
                        'already_member' => 'Already a member', default => 'Blank address skipped',
                    };
                    $notification = Notification::make()->title($message)->body($result->email);
                    if (in_array($result->status, ['invitation_created', 'member_added'])) {
                        if ($result->mailStatus === 'failed') {
                            $notification->warning()->body($result->email . '. Membership changes were saved, but notification delivery could not be requested.');
                        } else {
                            $notification->success()->body($result->email . '. ' . match ($result->mailStatus) {
                                'queued' => 'Notification queued for delivery.', 'sent' => 'Notification sent.', default => 'Notification will be requested after the transaction commits.'
                            });
                        }
                    } else {
                        $notification->warning();
                    }
                    $notification->send();
                }
            });
    }

    public static function attach(Closure $target): Action
    {
        return Action::make('attach')->label('Add existing members')
            ->authorize(fn () => Access::allows('viewMembers', $target()) && Access::users($target())->get()->contains(fn (Model $member) => Access::allows('addMember', [$target(), $member])))
            ->schema([Select::make('recordId')->label('Members')->multiple()->required()->searchable()
                // Label filtering is presentation; shared resolution and batch authorization validate submitted IDs.
                ->in(fn (Select $component) => $component->getState() ?? [])
                ->getSearchResultsUsing(fn (string $search) => Access::users($target())->where(fn ($query) => $query->where('name', 'like', '%' . $search . '%')->orWhere('email', 'like', '%' . $search . '%'))->limit(50)->get()->filter(fn (Model $user) => Access::allows('addMember', [$target(), $user]))->mapWithKeys(fn (Model $user) => [$user->getKey() => $user->email])->all())
                ->getOptionLabelsUsing(fn (array $values) => Access::users($target())->whereKey($values)->get()->filter(fn (Model $user) => Access::allows('addMember', [$target(), $user]))->mapWithKeys(fn (Model $user) => [$user->getKey() => $user->email])->all())])
            ->action(function (array $data) use ($target) {
                $actor = Access::actor();
                $record = $target();
                $users = app(MembershipCandidates::class)->resolveSelection($actor, $record, (array) $data['recordId']);
                app(MembershipBatch::class)->handle($actor, 'add_member', $users, $record);
            });
    }

    public static function remove(Closure $target): Action
    {
        return Action::make('detach')->label('Remove member')->requiresConfirmation()
            ->authorize(fn (Model $record) => (string) $record->getKey() !== (string) Access::actor()->getAuthIdentifier() && Access::allows('removeMember', [$target(), $record]))
            ->action(fn (Model $record) => app(RemoveMember::class)->handle(Access::actor(), $target(), $record));
    }
}
