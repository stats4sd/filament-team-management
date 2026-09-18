<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Filament\Support\Access;
use Stats4sd\FilamentTeamManagement\Filament\Support\MembershipTables;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    public function isReadOnly(): bool
    {
        return false;
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Members';
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return Access::allows('viewMembers', $ownerRecord);
    }

    public function table(Table $table): Table
    {
        return MembershipTables::members($table, fn () => $this->getOwnerRecord(), true);
    }
}
