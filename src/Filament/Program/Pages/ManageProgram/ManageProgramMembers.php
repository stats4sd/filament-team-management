<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Program\Pages\ManageProgram;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Livewire\Component;

class ManageProgramMembers extends TableWidget
{

    public function table(Table $table): Table
    {
        return ProgramMembersTable::configure($table);
    }

}
