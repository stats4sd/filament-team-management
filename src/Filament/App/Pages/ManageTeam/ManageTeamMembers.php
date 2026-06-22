<?php

namespace Stats4sd\FilamentTeamManagement\Filament\App\Pages\ManageTeam;

use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ManageTeamMembers extends TableWidget
{
    public function table(Table $table): Table
    {
        return TeamMembersTable::configure($table);
    }
}
