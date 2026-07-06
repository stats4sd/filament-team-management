<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Programs\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Callout;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Stats4sd\FilamentTeamManagement\Models\Interfaces\ProgramInterface;

class UsersRelationManager extends RelationManager
{
    // Hardcode relationship names, so we can use the relationships defined in the base models.
    protected static string $relationship = 'users';

    protected static ?string $inverseRelationship = 'programs';

    // turn on Edit mode so that "Add Existing User to program" button will be shown when viewing program record
    public function isReadOnly(): bool
    {
        return false;
    }

    // customise relation manager title
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return Str::ucfirst(Str::plural(config('filament-team-management.table_names.users')));
    }

    // Phase 2: program_members has no is_admin column and program-admin
    // enforcement is deferred to Phase 2. A program-admin concept (pivot flag +
    // an EditAction to set it, mirroring the Teams relation manager) would land
    // here. The previous name-editing form() was dead (no EditAction opened it,
    // and editing a user's name from a program pivot was wrong) and was removed.

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
                Action::make('invite ' . config('filament-team-management.table_names.users'))
                    ->schema([
                        Callout::make('Invitation')
                            ->info()
                            ->description('Add the email address(es) of the user(s) you would like to invite to this program. An invitation will be sent to each address.')
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
                    ->label('Add existing ' . Str::singular(config('filament-team-management.table_names.users')) . ' to program'),
            ])
            ->recordActions([
                DetachAction::make()->label('Remove ' . config('filament-team-management.models.user')::getModelNameLower())
                    ->modalSubmitActionLabel('Remove ' . config('filament-team-management.models.user')::getModelNameLower())
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
