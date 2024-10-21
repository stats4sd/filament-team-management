<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Program\Resources\ProgramResource\Pages;

use Stats4sd\FilamentTeamManagement\Filament\Program\Resources\ProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProgram extends EditRecord
{
    protected static string $resource = ProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
}
