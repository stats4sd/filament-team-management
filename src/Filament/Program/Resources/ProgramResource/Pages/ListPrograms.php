<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Program\Resources\ProgramResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Stats4sd\FilamentTeamManagement\Filament\Program\Resources\ProgramResource;

class ListPrograms extends ListRecords
{
    protected static string $resource = ProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
