<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Program\Resources\ProgramResource\Pages;

use App\Filament\Program\Resources\ProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

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
