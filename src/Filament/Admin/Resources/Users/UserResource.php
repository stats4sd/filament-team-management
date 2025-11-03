<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Users;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Users\Pages\ListUsers;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Users\Schemas\UserForm;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Users\Tables\UserTable;
use Stats4sd\FilamentTeamManagement\Filament\Traits\HasTeamManagementNavigationGroup;

class UserResource extends Resource
{
    use HasTeamManagementNavigationGroup;

    protected static string | null | \BackedEnum $navigationIcon = 'heroicon-o-users';

    public static function getModel(): string
    {
        return config('filament-team-management.models.user');
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserTable::configure($table);
    }

    public static function getRelations(): array
    {
        // Note: It would be nice to have a role invites relation manager to show all role_invites sent
        // Considering we should have small amount of role_invites, categorise it as "Nice to have" and develop it at later stage when we have time

        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
        ];
    }
}
