<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources;

use Spatie\Permission\Models\Role;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\RoleResource\Pages;

class RoleResource extends \Althinect\FilamentSpatieRolesPermissions\Resources\RoleResource
{
    protected static ?string $model = Role::class;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageRoles::route('/'),
        ];
    }
}
