<?php

namespace Stats4sd\FilamentTeamManagement\Filament\App\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Database\Eloquent\Model;

class RegisterTeam extends RegisterTenant
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function getLabel(): string
    {
        return 'Register New Team';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Enter a name for the team'),
            ]);
    }

    protected function handleRegistration(array $data): Model
    {
        $team = config('filament-team-management.models.team')::create($data);

        $team->members()->attach(auth()->user());

        return $team;
    }
}
