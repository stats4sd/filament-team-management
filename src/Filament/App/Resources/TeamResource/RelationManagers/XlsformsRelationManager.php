<?php

namespace Stats4sd\FilamentTeamManagement\Filament\App\Resources\TeamResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class XlsformsRelationManager extends RelationManager
{
    protected static string $relationship = 'xlsforms';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->grow(false)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\ViewColumn::make('team_datasets_required')
                    ->view('filament-odk-link::filament.tables.columns.team-datasets-required'),
                Tables\Columns\TextColumn::make('submissions_count')->counts('submissions')
                    ->label('No. of Submissions')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
