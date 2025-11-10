<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Program\Pages\ManageProgram;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ManageProgramInvites extends TableWidget
{
    public function table(Table $table): Table
    {
        return ProgramInvitesTable::configure($table);
    }
}
