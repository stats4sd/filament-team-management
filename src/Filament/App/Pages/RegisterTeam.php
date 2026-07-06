<?php

namespace Stats4sd\FilamentTeamManagement\Filament\App\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class RegisterTeam extends RegisterTenant
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function getLabel(): string
    {
        return 'Register New Team';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label('Enter a name for the team')
                    ->required(),
            ]);
    }

    protected function handleRegistration(array $data): Model
    {
        $team = config('filament-team-management.models.team')::create($data);

        // The user who registers a team becomes its admin. Attach via users()
        // (the unfiltered pivot relation) with the is_admin pivot flag set.
        // Phase 2: is_admin is set here but not yet enforced (intra-team
        // authorization based on the flag is deferred to Phase 2).
        $team->users()->attach(auth()->user(), ['is_admin' => true]);

        return $team;
    }
}
