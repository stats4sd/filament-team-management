<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\TeamResource\RelationManagers;

use App\Models\Team;
use App\Models\User;
use Awcodes\Shout\Components\Shout;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
// use Stats4sd\FilamentTeamManagement\Models\Team;
// use Stats4sd\FilamentTeamManagement\Models\User;

use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    // turn on Edit mode so that "Add Existing User to team" button will be showed when viewing team record
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Shout::make('info')
                    ->content(fn (User $record) => new HtmlString("Edit user's role within this team<br/>$record->name ($record->email)")),
                Forms\Components\Checkbox::make('is_admin')
                    ->label(fn (User $record): string => "$record->name is a Team Admin")
                    ->helperText('Team Admins have full access to all team settings and can manage all team members. They can edit or delete data. Non-admins can only collect data and view data.'),
            ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                // hide column "is_admin" as team admin is not being used in this application
                // keep below commented code, it will be used in other application
                // Tables\Columns\IconColumn::make('is_admin')
                //     ->label('Is a Team Admin?')
                //     ->boolean(),

                Tables\Columns\TextColumn::make('created_at'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\Action::make('invite users')
                    ->form([
                        Shout::make('info')
                            ->type('info')
                            ->content('Add the email address(es) of the user(s) you would like to invite to this team. An invitation will be sent to each address.')
                            ->columnSpanFull(),
                        Forms\Components\Repeater::make('users')
                            ->label('Email Addresses to Invite')
                            ->simple(
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                            )
                            ->reorderable(false)
                            ->addActionLabel('Add Another Email Address'),
                    ])
                    ->action(fn (array $data, RelationManager $livewire) => $this->handleInvitation($data, $livewire->getOwnerRecord())),
                Tables\Actions\AttachAction::make()
                    ->label('Add Existing User to team'),
            ])
            ->actions([
                // hide "Edit User Role" button as team admin is not being used in this application
                // keep below commented code, it will be used in other application
                // Tables\Actions\EditAction::make()->label('Edit User Role'),

                Tables\Actions\DetachAction::make()->label('Remove User')
                    ->modalSubmitActionLabel('Remove User')
                    ->modalHeading('Remove User from Team'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()->label('Remove selected')
                        ->modalSubmitActionLabel('Remove Selected Users')
                        ->modalHeading('Remove Selected Users from Team'),
                ]),
            ]);
    }

    public function handleInvitation(array $data, Team $team): void
    {
        $team->sendInvites($data['users']);
    }
}
