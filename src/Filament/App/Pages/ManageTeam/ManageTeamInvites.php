<?php

namespace Stats4sd\FilamentTeamManagement\Filament\App\Pages\ManageTeam;

use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ManageTeamInvites extends TableWidget
{
    public function table(Table $table): Table
    {
        return TeamInvitesTable::configure($table);
    }
}
