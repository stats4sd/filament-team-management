<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TeamTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('programs.name')
                    ->searchable()
                    ->badge()
                    ->color('success')
                    ->visible(config('filament-team-management.use_programs')),
                TextColumn::make('users_count')
                    ->label('# Users')
                    ->counts('users')
                    ->sortable(),
                TextColumn::make('invites_count')
                    ->label('# Invites')
                    ->counts('invites')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->sortable(),
            ]);
    }
}
