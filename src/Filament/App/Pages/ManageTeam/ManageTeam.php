<?php

namespace Stats4sd\FilamentTeamManagement\Filament\App\Pages\ManageTeam;

use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Stats4sd\FilamentTeamManagement\Filament\Support\Access;
use Stats4sd\FilamentTeamManagement\Filament\Support\MembershipNavigation;
use Stats4sd\FilamentTeamManagement\Filament\Traits\ManagesMembershipProfile;

class ManageTeam extends EditTenantProfile
{
    use ManagesMembershipProfile;

    protected static string | null | \BackedEnum $navigationIcon = 'heroicon-o-document-text';

    public function getHeading(): string | Htmlable | null
    {
        return 'Manage ' . config('filament-team-management.names.team') . ': ' . Filament::getTenant()->name;
    }

    public function getSubheading(): string | Htmlable | null
    {
        if (config('filament-team-management.use_programs')) {

            $programTypeName = Str::ucwords(config('filament-team-management.names.program'));

            $programLinks = MembershipNavigation::programLinks(Filament::getTenant());

            return new HtmlString($programTypeName . ': ' . $programLinks);

        }

        return '';
    }

    public static function getLabel(): string
    {
        $teamTypeName = config('filament-team-management.names.team');

        return 'Manage ' . ucfirst($teamTypeName);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->disabled(fn () => ! Access::allows('update', $this->tenant))
                ->label('Enter a name for the ' . config('filament-team-management.names.team')),
            Textarea::make('description')
                ->disabled(fn () => ! Access::allows('update', $this->tenant))
                ->rows(5)
                ->label('Enter a brief description for the ' . config('filament-team-management.names.team')),
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
                        Tabs\Tab::make('Members')->visible(fn () => Access::allows('viewMembers', $this->tenant))
                            ->schema([
                                Livewire::make(ManageTeamMembers::class)->key('manage-team-members'),
                            ]),
                        Tabs\Tab::make('Invitations')->visible(fn () => Access::allows('viewInvitations', $this->tenant))
                            ->schema([
                                Livewire::make(ManageTeamInvites::class)->key('manage-team-invites'),
                            ]),
                    ]),
            ]);
    }
}
