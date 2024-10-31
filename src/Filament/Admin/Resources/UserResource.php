<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\UserResource\Pages;

class UserResource extends Resource
{
    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function getModel(): string
    {
        return config('filament-team-management.models.user');
    }

    public static function getNavigationGroup(): string
    {
        if (config('filament-team-management.use_programs')) {
            return 'Programs, Teams and Users';
        } else {
            return 'Teams and Users';
        }
    }

    public static function form(Form $form): Form
    {
        $teamClass = config('filament-team-management.models.team');

        return $form
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
                    ->required($form->getRecord() == null)
                    ->password()
                    ->revealable(),

                // TODO probably remove password editing here;

                // invite to team (if role is team member)

                // TODO: make multiple select
                //
                // (It's also because there seems to be a bug in Filament where select multiples don't work if the disabled() state is live updated...)
                Select::make('team_id')
                    ->label('Which team should the user be a member of?')
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('programs.name')
                    ->searchable()
                    ->badge()
                    ->color('success')
                    ->visible(config('filament-team-management.use_programs')),
                Tables\Columns\TextColumn::make('teams.name')
                    ->searchable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        // Note: It would be nice to have a role invites relation manager to show all role_invites sent
        // Considering we should have small amount of role_invites, categorise it as "Nice to have" and develop it at later stage when we have time

        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
