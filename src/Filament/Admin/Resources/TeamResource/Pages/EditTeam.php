<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\TeamResource\Pages;

use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\TeamResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTeam extends EditRecord
{
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->modalDescription('WARNING: Please do not delete when there is actual survey data collected, as deletion is unreversable. Are you sure you would like to do this?'),
        ];
    }
}
