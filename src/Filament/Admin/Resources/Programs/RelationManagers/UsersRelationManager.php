<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\ProgramResource\RelationManagers;

use Awcodes\Shout\Components\Shout;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Stats4sd\FilamentTeamManagement\Models\Interfaces\ProgramInterface;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    // turn on Edit mode so that "Add Existing User to program" button will be shown when viewing program record
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
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
                Tables\Columns\TextColumn::make('created_at'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('invite users')
                    ->form([
                        Shout::make('info')
                            ->type('info')
                            ->content('Add the email address(es) of the user(s) you would like to invite to this program. An invitation will be sent to each address.')
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
                AttachAction::make()
                    ->label('Add Existing User to program'),
            ])
            ->recordActions([
                DetachAction::make()->label('Remove User')
                    ->modalSubmitActionLabel('Remove User')
                    ->modalHeading('Remove User from Program'),
            ])
            ->groupedBulkActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()->label('Remove selected')
                        ->modalSubmitActionLabel('Remove Selected Users')
                        ->modalHeading('Remove Selected Users from Program'),
                ]),
            ]);
    }

    public function handleInvitation(array $data, ProgramInterface $program): void
    {
        $program->sendInvites($data['users']);
    }
}
