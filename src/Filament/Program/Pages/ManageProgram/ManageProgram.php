<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Program\Pages\ManageProgram;

use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Stats4sd\FilamentTeamManagement\Filament\Support\Access;
use Stats4sd\FilamentTeamManagement\Filament\Traits\ManagesMembershipProfile;

class ManageProgram extends EditTenantProfile
{
    use ManagesMembershipProfile;

    protected static string | null | \BackedEnum $navigationIcon = 'heroicon-o-document-text';

    public static function getLabel(): string
    {
        return 'Manage ' . Str::ucfirst(config('filament-team-management.names.program'));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->disabled(fn () => ! Access::allows('update', $this->tenant))
                    ->label('Enter a name for the ' . config('filament-team-management.names.program')),
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
                Tabs::make('Memberships')
                    ->contained(false)
                    ->tabs([
                        Tabs\Tab::make(Str::ucfirst(Str::plural(config('filament-team-management.names.team'))))->visible(fn () => Access::allows('viewTeams', $this->tenant))
                            ->schema([
                                Livewire::make(ManageProgramTeams::class)
                                    ->key('manage-program-teams'),
                            ]),
                        Tabs\Tab::make('Members')->visible(fn () => Access::allows('viewMembers', $this->tenant))
                            ->schema([
                                Livewire::make(ManageProgramMembers::class)
                                    ->key('manage-program-members'),
                            ]),
                        Tabs\Tab::make('Invitations')->visible(fn () => Access::allows('viewInvitations', $this->tenant))
                            ->schema([
                                Livewire::make(ManageProgramInvites::class)
                                    ->key('manage-program-invites'),
                            ]),
                    ]),
            ]);
    }
}
