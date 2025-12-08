<?php

namespace Stats4sd\FilamentTeamManagement\Filament\App\Pages\ManageTeam;

use Filament\Actions\BulkActionGroup;
use Filament\Facades\Filament;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TeamInvitesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->relationship(fn () => Filament::getTenant()->invites())
            ->inverseRelationship('teams')
            ->columns([
                TextColumn::make('email'),
                TextColumn::make('role.name')->label('Roles assigned'),
                TextColumn::make('team.name')->label(config('filament-team-management.table_names.teams') . ' assigned'),
                TextColumn::make('project.name')->label(config('filament-team-management.table_names.programs') . ' assigned')
                    ->visible(fn () => config('filament-team-management.use_programs')),
                TextColumn::make('created_at')->dateTime(),
                IconColumn::make('is_confirmed')->boolean(),

            ])
            ->filters([
                Filter::make('only_unconfirmed')
                    ->label('Show accepted invites')
                    ->baseQuery(fn (Builder $query) => $query->withoutGlobalScope('onlyUnconfirmed'))
                    ->toggle(),
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
