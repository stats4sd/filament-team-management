<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\RoleResource\Pages;

use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

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
