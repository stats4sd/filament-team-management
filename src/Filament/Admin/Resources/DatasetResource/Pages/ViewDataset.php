<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\DatasetResource\Pages;

use App\Filament\Admin\Resources\DatasetResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDataset extends ViewRecord
{
    protected static string $resource = DatasetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
