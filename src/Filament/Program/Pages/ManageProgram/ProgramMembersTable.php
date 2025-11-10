<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Program\Pages\ManageProgram;

use Awcodes\Shout\Components\Shout;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProgramMembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->relationship(fn () => Filament::getTenant()->members())
            ->inverseRelationship('teams')
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('email'),
                TextColumn::make('roles.name'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('Invite')
                    ->schema([
                        Shout::make('info')
                            ->type('info')
                            ->content('Add the email address(es) of the user(s) you would like to invite to this '.config('filament-team-management.names.team').'. An invitation will be sent to each address.')
                            ->columnSpanFull(),
                        Repeater::make('users')
                            ->label('Email Addresses to Invite')
                            ->simple(
                                TextInput::make('email')
                                    ->email()
                                    ->required()
                            )
                            ->reorderable(false)
                            ->addActionLabel('Add Another Email Address'),
                    ])
                    ->action(fn (array $data) => Filament::getTenant()->sendInvites($data['users'])),
                AttachAction::make('Add Existing Users')
                    ->recordTitleAttribute('email')
                    ->multiple(),

            ])
            ->recordActions([
                DetachAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
