<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\ProgramResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\ProgramResource;

class EditProgram extends EditRecord
{
    protected static string $resource = ProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->modalDescription('WARNING: Please do not delete when there is actual survey data collected, as deletion is irreversible. Are you sure you would like to do this?'),
        ];
    }
}
