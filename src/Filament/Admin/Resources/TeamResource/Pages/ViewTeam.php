<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\TeamResource\Pages;

use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\TeamResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewTeam extends ViewRecord
{
    protected static string $resource = TeamResource::class;

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
