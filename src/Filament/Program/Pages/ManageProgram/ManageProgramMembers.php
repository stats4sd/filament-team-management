<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Program\Pages\ManageProgram;

use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ManageProgramMembers extends TableWidget
{
    public function table(Table $table): Table
    {
        return ProgramMembersTable::configure($table);
    }
}
