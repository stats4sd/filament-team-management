<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Users\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $teamClass = config('filament-team-management.models.team');

        return $schema
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('Email')
                    ->placeholder('Email')
                    ->email()
                    ->required(),

                TextInput::make('password')
                    ->label('Password')
                    ->placeholder('Password')
                    // password field is compulsory when creating a new user record, it is optional when editing an existing user record
                    ->required($schema->getRecord() == null)
                    ->password()
                    ->revealable(),

                // TODO: make multiple select
                //
                // (It's also because there seems to be a bug in Filament where select multiples don't work if the disabled() state is live updated...)
                Select::make('team_id')
                    ->label('Which ' . config('filament-team-management.names.team') . ' should the user be a member of?')
                    ->exists((new $teamClass)->getTable(), 'id')
                    ->relationship('teams', titleAttribute: 'name'),

                // invite to role
                CheckboxList::make('roles')
                    ->relationship('roles', titleAttribute: 'name')
                    ->label('Select the user role(s) to assign')
                    ->exists('roles', 'id')
                    ->live(),
            ]);
    }
}
