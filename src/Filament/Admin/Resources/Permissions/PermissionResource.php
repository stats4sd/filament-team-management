<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Permissions;

use Spatie\Permission\Models\Permission;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Permissions\Pages\ManagePermissions;

class PermissionResource extends \Althinect\FilamentSpatieRolesPermissions\Resources\PermissionResource
{
    protected static ?string $model = Permission::class;

    public static function getPages(): array
    {
        return [
            'index' => ManagePermissions::route('/'),
        ];
    }
}
