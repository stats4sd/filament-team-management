<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Programs\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Programs\ProgramResource;
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
        $modelName = config('filament-team-management.names.program');

        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->modalDescription("WARNING: This will permanently delete this $modelName and all associated teams and data. This action cannot be undone."),
        ];
    }
}
