<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Program\Pages\ManageProgram;

use Filament\Facades\Filament;
use Filament\Tables\Table;
use Stats4sd\FilamentTeamManagement\Filament\Support\MembershipTables;

class ProgramMembersTable
{
    public static function configure(Table $table): Table
    {
        return MembershipTables::members($table, fn () => Filament::getTenant(), true);
    }
}
