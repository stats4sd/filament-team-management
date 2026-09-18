<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Actions\DeleteTeam;
use Stats4sd\FilamentTeamManagement\Actions\UpdateTeam;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\TeamResource;
use Stats4sd\FilamentTeamManagement\Filament\Support\Access;
use Stats4sd\FilamentTeamManagement\Filament\Support\MembershipNavigation;

class ViewTeam extends ViewRecord
{
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->using(fn (Model $record, array $data) => app(UpdateTeam::class)->handle(Access::actor(), $record, $data)),
            Actions\DeleteAction::make()->using(fn (Model $record) => app(DeleteTeam::class)->handle(Access::actor(), $record))->modalDescription('Deleting this ' . config('filament-team-management.names.team') . ' is irreversible. Memberships and invitations will be removed.')->successRedirectUrl(fn () => MembershipNavigation::afterDeparture(true)),
        ];
    }
}
