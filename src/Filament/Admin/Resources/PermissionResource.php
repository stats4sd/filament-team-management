<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PermissionResource\Pages;
use Spatie\Permission\Models\Permission;

class PermissionResource extends \Althinect\FilamentSpatieRolesPermissions\Resources\PermissionResource
{
    protected static ?string $model = Permission::class;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePermissions::route('/'),
        ];
    }
}
