<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Roles;

use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Roles\Pages\ManageRoles;

class RoleResource extends \Althinect\FilamentSpatieRolesPermissions\Resources\RoleResource
{
    public static function getModel(): string
    {
        return config('filament-team-management.models.role');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRoles::route('/'),
        ];
    }
}
