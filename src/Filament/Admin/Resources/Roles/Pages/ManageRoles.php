<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Roles\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Roles\RoleResource;

class ManageRoles extends ManageRecords
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
