<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Program\Pages\ManageProgram;

use Filament\Facades\Filament;
use Filament\Tables\Table;
use Stats4sd\FilamentTeamManagement\Filament\Support\ProgramTeams;

class ProgramTeamsTable
{
    public static function configure(Table $table): Table
    {
        return ProgramTeams::configure($table, fn () => Filament::getTenant());
    }
}
