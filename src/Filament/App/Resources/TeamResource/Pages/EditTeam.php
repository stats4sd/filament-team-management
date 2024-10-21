<?php

namespace Stats4sd\FilamentTeamManagement\Filament\App\Resources\TeamResource\Pages;

use Stats4sd\FilamentTeamManagement\Filament\App\Resources\TeamResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTeam extends EditRecord
{
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
}
