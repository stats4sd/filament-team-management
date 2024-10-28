<?php

namespace Stats4sd\FilamentTeamManagement\Filament\App\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Database\Eloquent\Model;

class RegisterProgram extends RegisterTenant
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function getLabel(): string
    {
        return 'Register New Program';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Enter a name for the program'),
            ]);
    }

    protected function handleRegistration(array $data): Model
    {
        $program = config('filament-team-management.models.program')::create($data);

        $program->users()->attach(auth()->user());

        return $program;
    }
}
