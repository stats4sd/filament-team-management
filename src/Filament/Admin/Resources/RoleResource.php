<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources;

use App\Filament\Admin\Resources\RoleResource\Pages;
use Spatie\Permission\Models\Role;

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
