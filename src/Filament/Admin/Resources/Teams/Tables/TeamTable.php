<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

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
                    ->label('# ' . ucfirst(Str::plural(config('filament-team-management.names.user'))))
                    ->counts('users')
                    ->sortable(),
                TextColumn::make('invites_count')
                    ->label('# Pending invites')
                    ->counts(['invites' => fn ($query) => $query->pending()])
                    ->sortable(),
                TextColumn::make('created_at')
                    ->sortable(),
            ]);
    }
}
