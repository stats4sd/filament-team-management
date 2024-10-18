<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\DatasetResource\Pages;

use App\Filament\Admin\Resources\DatasetResource;
use App\Filament\Traits\RedirectsToListAfterSave;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDataset extends EditRecord
{
    use RedirectsToListAfterSave;

    protected static string $resource = DatasetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ViewAction::make(),
        ];
    }
}
