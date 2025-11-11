<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Program\Pages\ManageProgram;

use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ManageProgram extends EditTenantProfile
{
    protected static string | null | \BackedEnum $navigationIcon = 'heroicon-o-document-text';

    public static function getLabel(): string
    {
        $programTypeName = config('filament-team-management.models.program')::getModelNameLower();

        return 'Manage ' . ucfirst($programTypeName);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label('Enter a name for the ' . Str::ucwords(config('filament-team-management.models.program')::getModelNameLower())),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->schema([
                        $this->getFormContentComponent(),
                    ]),
                Tabs::make('User Management')
                    ->contained(false)
                    ->tabs([
                        Tabs\Tab::make('Members')
                            ->schema([
                                Livewire::make(ManageProgramMembers::class)
                                    ->key('manage-program-members'),
                            ]),
                        Tabs\Tab::make('Invites')
                            ->schema([
                                Livewire::make(ManageProgramInvites::class)
                                    ->key('manage-program-invites'),
                            ]),
                    ]),
            ]);
    }
}
