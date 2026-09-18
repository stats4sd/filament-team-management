<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Stats4sd\FilamentTeamManagement\Actions\CreateTeam;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\TeamResource;
use Stats4sd\FilamentTeamManagement\Filament\Support\Access;

class ListTeams extends ListRecords
{
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->using(fn (array $data) => app(CreateTeam::class)->handle(Access::actor(), $data)),
        ];
    }
}
