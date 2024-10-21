<?php

namespace Stats4sd\FilamentTeamManagement\Filament\App\Pages;

use Stats4sd\FilamentTeamManagement\Models\Program;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant;

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

    protected function handleRegistration(array $data): Program
    {
        $program = Program::create($data);

        $program->users()->attach(auth()->user());

        return $program;
    }
}
