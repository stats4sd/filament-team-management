<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\DatasetResource\Pages;

use App\Filament\Admin\Resources\DatasetResource;
use App\Filament\Traits\RedirectsToListAfterSave;
use Filament\Resources\Pages\CreateRecord;

class CreateDataset extends CreateRecord
{
    use RedirectsToListAfterSave;

    protected static string $resource = DatasetResource::class;
}
