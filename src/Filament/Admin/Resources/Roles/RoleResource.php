<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources;

use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\RoleResource\Pages;

class RoleResource extends \Althinect\FilamentSpatieRolesPermissions\Resources\RoleResource
{
    public static function getModel(): string
    {
        return config('filament-team-management.models.role');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageRoles::route('/'),
        ];
    }
}
