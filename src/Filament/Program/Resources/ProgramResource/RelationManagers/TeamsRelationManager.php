<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Program\Resources\ProgramResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TeamsRelationManager extends RelationManager
{
    protected static string $relationship = 'teams';

    // turn on Edit mode so that "Add Existing Team to program" button will be showed when viewing program record
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
                Tables\Columns\TextColumn::make('name'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\AttachAction::make()
                    ->label('Add Existing Team to program'),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()->label('Remove Team')
                    ->modalSubmitActionLabel('Remove Team')
                    ->modalHeading('Remove Team from Program'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()->label('Remove selected')
                        ->modalSubmitActionLabel('Remove Selected Teams')
                        ->modalHeading('Remove Selected Teams from Program'),
                ]),
            ]);
    }
}