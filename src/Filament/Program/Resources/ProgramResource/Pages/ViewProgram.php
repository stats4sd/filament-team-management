<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Program\Resources\ProgramResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Stats4sd\FilamentTeamManagement\Filament\Program\Resources\ProgramResource;
use Stats4sd\FilamentTeamManagement\Models\Program;

/** @method Program getRecord() */
class ViewProgram extends ViewRecord
{
    protected static string $resource = ProgramResource::class;

    public function getTitle(): string | Htmlable
    {
        return $this->getRecord()->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->modalDescription('WARNING: Please do not delete when there is actual survey data collected, as deletion is unreversable. Are you sure you would like to do this?'),
        ];
    }
}
