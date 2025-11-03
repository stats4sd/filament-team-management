<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Programs;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\ProgramResource\Pages;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\ProgramResource\RelationManagers;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Programs\Schemas\ProgramForm;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Programs\Schemas\ProgramInfolist;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Programs\Tables\ProgramTable;

class ProgramResource extends Resource
{
    protected static string | null | \BackedEnum $navigationIcon = 'heroicon-o-building-library';

    public static function getModel(): string
    {
        return config('filament-team-management.models.program');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return config('filament-team-management.use_programs');
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
        return ProgramForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProgramInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProgramTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TeamsRelationManager::class,
            RelationManagers\UsersRelationManager::class,
            RelationManagers\InvitesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrograms::route('/'),
            'view' => Pages\ViewProgram::route('/{record}'),
        ];
    }
}
