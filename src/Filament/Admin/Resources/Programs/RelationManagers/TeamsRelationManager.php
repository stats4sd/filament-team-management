<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Programs\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Stats4sd\FilamentTeamManagement\Filament\Support\Access;
use Stats4sd\FilamentTeamManagement\Filament\Support\ProgramTeams;

class TeamsRelationManager extends RelationManager
{
    protected static string $relationship = 'teams';

    public function isReadOnly(): bool
    {
        return false;
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return ucfirst(Str::plural(config('filament-team-management.names.team')));
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return Access::allows('viewTeams', $ownerRecord);
    }

    public function table(Table $table): Table
    {
        return ProgramTeams::configure($table, fn () => $this->getOwnerRecord());
    }
}
