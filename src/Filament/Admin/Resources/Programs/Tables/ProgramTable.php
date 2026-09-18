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
                TextColumn::make('note')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('teams_count')
                    ->label('# ' . Str::of(config('filament-team-management.names.team'))->plural()->ucFirst())
                    ->counts('teams')
                    ->sortable(),
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
