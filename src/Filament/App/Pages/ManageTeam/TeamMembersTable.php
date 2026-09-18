<?php

namespace Stats4sd\FilamentTeamManagement\Filament\App\Pages\ManageTeam;

use Filament\Facades\Filament;
use Filament\Tables\Table;
use Stats4sd\FilamentTeamManagement\Filament\Support\MembershipTables;

class TeamMembersTable
{
    public static function configure(Table $table): Table
    {
        return MembershipTables::members($table, fn () => Filament::getTenant());
    }
}
