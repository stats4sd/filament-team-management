<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\PermissionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\PermissionResource;

class ManagePermissions extends ManageRecords
{
    protected static string $resource = PermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
