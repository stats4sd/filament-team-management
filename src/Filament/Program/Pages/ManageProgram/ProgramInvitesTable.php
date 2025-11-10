<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Program\Pages\ManageProgram;

use Filament\Actions\BulkActionGroup;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProgramInvitesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->relationship(fn() => Filament::getTenant()->invites())
            ->inverseRelationship('teams')
            ->columns([
                TextColumn::make('email'),
                TextColumn::make('role.name')->label('Roles assigned'),
                TextColumn::make('team.name')->label('Teams assigned'),
                TextColumn::make('created_at')->dateTime(),

            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->filters([
                Filter::make('only_unconfirmed')
                    ->label('Show accepted invites')
                    ->baseQuery(fn(Builder $query) => $query->withoutGlobalScope('onlyUnconfirmed'))
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
