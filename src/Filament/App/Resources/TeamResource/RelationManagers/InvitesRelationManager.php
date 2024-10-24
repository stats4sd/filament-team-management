<?php

namespace Stats4sd\FilamentTeamManagement\Filament\App\Resources\TeamResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class InvitesRelationManager extends RelationManager
{
    protected static string $relationship = 'invites';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('team.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('inviter.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_confirmed')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
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
