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

                Select::make('teams')
                    ->multiple()
                    ->preload()
                    ->label('Which ' . config('filament-team-management.table_names.teams') . ' should the user be a member of?')
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
