<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\UserResource\Pages;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Users\Schemas\UserForm;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Users\Tables\UserTable;

class UserResource extends Resource
{
    protected static string | null | \BackedEnum $navigationIcon = 'heroicon-o-users';

    public static function getModel(): string
    {
        return config('filament-team-management.models.user');
    }

    public static function getNavigationGroup(): string
    {
        if (config('filament-team-management.use_programs')) {
            return 'Programs, ' . Str::ucfirst(Str::plural(config('filament-team-management.names.team'))) . ' and Users';
        } else {
            return Str::ucfirst(Str::plural(config('filament-team-management.names.team'))) . ' and Users';
        }
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
