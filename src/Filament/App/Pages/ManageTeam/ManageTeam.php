<?php

namespace Stats4sd\FilamentTeamManagement\Filament\App\Pages\ManageTeam;

use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Resources\Pages\Concerns\HasRelationManagers;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ManageTeam extends EditTenantProfile
{
    // use HasRelationManagers;

    protected static string | null | \BackedEnum $navigationIcon = 'heroicon-o-document-text';

    //   protected string $view = 'filament-team-management::filament.app.pages.manage-team';

    public function getHeading(): string | Htmlable | null
    {
        return 'Manage ' . config('filament-team-management.models.team')::getModelNameLower() . ': ' . Filament::getTenant()->name;
    }

    public function getSubheading(): string | Htmlable | null
    {
        if (config('filament-team-management.use_programs')) {

            $programTypeName = Str::ucwords(config('filament-team-management.models.program')::getModelNameLower());

            $programLinks = Filament::getTenant()?->programs?->map(function ($program) {
                $url = url('/program/' . $program->id);

                return '<a href="' . $url . '" class="underline text-primary-600 hover:text-primary-700 focus:text-primary-700 transition">' . e($program->name) . '</a>';
            })->join(', ');

            return new HtmlString($programTypeName . ': ' . $programLinks);

        }

        return '';
    }

    public static function getLabel(): string
    {
        $teamTypeName = config('filament-team-management.models.team')::getModelNameLower();

        return 'Manage ' . ucfirst($teamTypeName);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label('Enter a name for the ' . config('filament-team-management.models.team')::getModelNameLower()),
            Textarea::make('description')
                ->rows(5)
                ->label('Enter a brief description for the ' . config('filament-team-management.models.team')::getModelNameLower()),
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
                Tabs::make('Team Management')
                    ->contained(false)
                    ->tabs([
                        Tabs\Tab::make('Members')
                            ->schema([
                                Livewire::make(ManageTeamMembers::class)->key('manage-team-members'),
                            ]),
                        Tabs\Tab::make('Invites')
                            ->schema([
                                Livewire::make(ManageTeamInvites::class)->key('manage-team-invites'),
                            ]),
                    ]),
            ]);
    }
}
