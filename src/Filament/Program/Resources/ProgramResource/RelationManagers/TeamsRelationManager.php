<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Program\Resources\ProgramResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\RelationManagers\RelationManager;

class TeamsRelationManager extends RelationManager
{
    protected static string $relationship = 'teams';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return TeamsRelationManager::getModelNamePlural();
    }

    public static function getModelName(): string
    {
        return Str::ucfirst(config('filament-team-management.names.team'));
    }

    public static function getModelNamePlural(): string
    {
        // return Str::ucfirst(Str::plural(config('filament-team-management.names.team')));
        return Str::plural(TeamsRelationManager::getModelName());
    }

    // turn on Edit mode so that "Add Existing Team to program" button will be shown when viewing program record
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
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
                Tables\Columns\TextColumn::make('programs.name')
                    ->searchable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('# Users')
                    ->counts('users')
                    ->sortable(),
                Tables\Columns\TextColumn::make('invites_count')
                    ->label('# Invites')
                    ->counts('invites')
                    ->sortable(),

                // Note: Here it is using filament-team-management Team model, which does not have xlsform relationship yet.
                // xlsform relationship is added in main repo Team model because it uses HasXlsForms trait.
                // I think we can comprimise to not show number of xlsforms belongs to a team here.

                // Tables\Columns\TextColumn::make('xlsforms_count')
                //     ->label('# Xlsforms')
                //     ->counts('xlsforms')
                //     ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\AttachAction::make()
                    ->label('Add Existing ' . TeamsRelationManager::getModelName() . ' to program'),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()->label('Remove ' . TeamsRelationManager::getModelName())
                    ->modalSubmitActionLabel('Remove ' . TeamsRelationManager::getModelName())
                    ->modalHeading('Remove ' . TeamsRelationManager::getModelName() . ' from Program'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()->label('Remove selected')
                        ->modalSubmitActionLabel('Remove Selected ' . TeamsRelationManager::getModelNamePlural())
                        ->modalHeading('Remove Selected ' . TeamsRelationManager::getModelNamePlural() . ' from Program'),
                ]),
            ]);
    }
}
