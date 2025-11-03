<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Programs\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProgramTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('description')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('note')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('teams_count')
                    ->label('# ' . Str::ucfirst(Str::plural(config('filament-team-management.names.team'))))
                    ->counts('teams')
                    ->sortable(),
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
