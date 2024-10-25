<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\UserResource\Pages;
use Stats4sd\FilamentTeamManagement\Models\User;

class UserResource extends Resource
{
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $model = User::class;

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
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                \Filament\Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->placeholder('Email')
                    ->email()
                    ->required(),

                \Filament\Forms\Components\TextInput::make('password')
                    ->label('Password')
                    ->placeholder('Password')
                    // password field is compulsory when creating a new user record, it is optional when editing an existing user record
                    ->required($form->getRecord() == null)
                    ->password()
                    ->revealable(),

                // invite to team (if role is team member)

                // Note - The default setup is to have 'teams' as a belongsToMany for users. This is still the case in this platform (to avoid re-writing the structure), but in this case users will only ever belong to zero or one team. So this is not a 'multiple'.
                //
                // (It's also because there seems to be a bug in Filament where select multiples don't work if the disabled() state is live updated...)
                \Filament\Forms\Components\Select::make('team_id')
                    ->label('Which team should the user be a member of?')
                    ->exists('teams', 'id')
                    ->relationship('teams', titleAttribute: 'name'),

                // invite to role
                \Filament\Forms\Components\CheckboxList::make('roles')
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
