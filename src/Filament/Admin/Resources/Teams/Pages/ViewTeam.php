<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\TeamResource;
use Stats4sd\FilamentTeamManagement\Models\Team;

/** @method Team getRecord() */
class ViewTeam extends ViewRecord
{
    protected static string $resource = \Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\TeamResource::class;

    public function getTitle(): string | Htmlable
    {
        return $this->getRecord()->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->modalDescription('WARNING: This will permanently delete this team and all associated data. This action cannot be undone. Do you wish to continue?'),
        ];
    }
}
