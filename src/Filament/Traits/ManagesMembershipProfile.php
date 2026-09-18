<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Traits;

use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Actions\LeaveMembership;
use Stats4sd\FilamentTeamManagement\Actions\UpdateProgram;
use Stats4sd\FilamentTeamManagement\Actions\UpdateTeam;
use Stats4sd\FilamentTeamManagement\Filament\Support\Access;
use Stats4sd\FilamentTeamManagement\Filament\Support\MembershipNavigation;
use Stats4sd\FilamentTeamManagement\Support\Membership;

trait ManagesMembershipProfile
{
    public static function canView(Model $tenant): bool
    {
        $program = config('filament-team-management.models.program');

        return (! ($tenant instanceof $program) || config('filament-team-management.use_programs')) && Access::allows('view', $tenant);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $team = config('filament-team-management.models.team');

        return app($record instanceof $team ? UpdateTeam::class : UpdateProgram::class)->handle(Access::actor(), $record, $data);
    }

    protected function getFormActions(): array
    {
        return Access::allows('update', $this->tenant) ? parent::getFormActions() : [];
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('leave')->label('Leave membership')->requiresConfirmation()
            ->modalDescription('Your membership will be removed. You may need an invitation to rejoin.')
            ->authorize(fn () => Access::allows('leave', $this->tenant))
            ->action(function () {
                app(LeaveMembership::class)->handle(Access::actor(), $this->tenant);
                $this->redirect(MembershipNavigation::afterDeparture(departedType: Membership::type($this->tenant)));
            })];
    }
}
