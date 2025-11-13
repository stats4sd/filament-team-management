<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\RelationManagers\InvitesRelationManager;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\RelationManagers\UsersRelationManager;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\Schemas\TeamForm;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\Schemas\TeamInfolist;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\Tables\TeamTable;
use Stats4sd\FilamentTeamManagement\Filament\Traits\HasTeamManagementNavigationGroup;

// filament-odk-link package related code are commented as some applications may not require ODK functionalities.
// Please uncomment those code if filament-odk-link package is required and added to main repo.

class TeamResource extends Resource
{
    use HasTeamManagementNavigationGroup;

    protected static string | null | \BackedEnum $navigationIcon = 'heroicon-o-building-office-2';

    public static function getModel(): string
    {
        return config('filament-team-management.models.team');
    }

    public static function form(Schema $schema): Schema
    {
        return TeamForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TeamTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeams::route('/'),
            'view' => Pages\ViewTeam::route('/{record}'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            UsersRelationManager::class,
            InvitesRelationManager::class,
            // XlsformsRelationManager::class,
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return TeamInfolist::configure($schema);
    }
}
