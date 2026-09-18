<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Programs\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Filament\Support\Access;
use Stats4sd\FilamentTeamManagement\Filament\Support\MembershipTables;

class InvitesRelationManager extends RelationManager
{
    protected static string $relationship = 'invites';

    public function isReadOnly(): bool
    {
        return false;
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Invitations';
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return Access::allows('viewInvitations', $ownerRecord);
    }

    public function table(Table $table): Table
    {
        return MembershipTables::invites($table, fn () => $this->getOwnerRecord());
    }
}
