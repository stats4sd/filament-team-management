<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Support;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Stats4sd\FilamentTeamManagement\Actions\CancelMembershipInvitation;
use Stats4sd\FilamentTeamManagement\Actions\MembershipBatch;
use Stats4sd\FilamentTeamManagement\Actions\ResendMembershipInvitation;
use Stats4sd\FilamentTeamManagement\Models\Invite;
use Stats4sd\FilamentTeamManagement\Support\Membership;

class MembershipTables
{
    public static function members(Table $table, Closure $target, bool $attach = false): Table
    {
        Membership::type($target());
        Access::authorize('viewMembers', $target());

        return $table->relationship(fn () => $target()->users())->inverseRelationship(Membership::type($target()) === 'team' ? 'teams' : 'programs')
            ->columns([TextColumn::make('name')->searchable(), TextColumn::make('email')->searchable()])
            ->headerActions(array_filter([MemberActions::invite($target), $attach ? MemberActions::attach($target) : null]))
            ->recordActions([MemberActions::remove($target)])
            ->toolbarActions($attach ? [BulkAction::make('detach')->label('Remove selected')->requiresConfirmation()->action(function ($records, $livewire) use ($target) {
                $owner = $target();
                $leavingCurrent = Filament::getTenant()?->is($owner) && $records->contains(fn ($member) => (string) $member->getKey() === (string) Access::actor()->getAuthIdentifier());
                app(MembershipBatch::class)->handle(Access::actor(), 'remove_member', $records, $owner);
                if ($leavingCurrent) {
                    $livewire->redirect(MembershipNavigation::afterDeparture(departedType: Membership::type($owner)));
                }
            })] : []);
    }

    public static function invites(Table $table, Closure $target): Table
    {
        Membership::type($target());
        Access::authorize('viewInvitations', $target());

        return $table->relationship(fn () => $target()->invites())->inverseRelationship(Membership::type($target()))
            ->columns([
                TextColumn::make('email')->searchable(),
                TextColumn::make('inviter.name')->label('Invited by')->placeholder('Former member'),
                TextColumn::make('status')->state(fn (Invite $record) => $record->isAccepted() ? 'Accepted' : ($record->isExpired() ? 'Expired' : 'Pending'))->badge(),
                TextColumn::make('expires_at')->dateTime()->placeholder('No expiry'),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->modifyQueryUsing(fn (Builder $query, $livewire) => ($livewire->getTableFilterState('show_accepted')['isActive'] ?? false) ? $query : $query->where('is_confirmed', false))
            ->filters([Filter::make('show_accepted')->label('Show accepted invites')->query(fn (Builder $query) => $query)])
            ->recordActions([
                Action::make('resend')->label('Resend')->requiresConfirmation()
                    ->authorize(fn (Invite $record) => ! $record->isAccepted() && Access::allows('resendInvitation', [$target(), $record]))
                    ->action(fn (Invite $record) => app(ResendMembershipInvitation::class)->handle(Access::actor(), $target(), $record)),
                Action::make('cancel')->label('Cancel invitation')->requiresConfirmation()
                    ->authorize(fn (Invite $record) => ! $record->isAccepted() && Access::allows('cancelInvitation', [$target(), $record]))
                    ->action(fn (Invite $record) => app(CancelMembershipInvitation::class)->handle(Access::actor(), $target(), $record)),
            ]);
    }
}
