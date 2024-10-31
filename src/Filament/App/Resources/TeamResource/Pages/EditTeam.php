<?php

namespace Stats4sd\FilamentTeamManagement\Filament\App\Resources\TeamResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Stats4sd\FilamentTeamManagement\Filament\App\Resources\TeamResource;

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
