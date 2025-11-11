<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Program\Pages\ManageProgram;

use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ManageProgramInvites extends TableWidget
{
    public function table(Table $table): Table
    {
        return ProgramInvitesTable::configure($table);
    }
}
