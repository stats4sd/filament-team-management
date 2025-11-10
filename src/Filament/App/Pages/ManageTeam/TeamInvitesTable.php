<?php

namespace Stats4sd\FilamentTeamManagement\Filament\App\Pages\ManageTeam;

use Filament\Actions\BulkActionGroup;
use Filament\Facades\Filament;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

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
                TextColumn::make('team.name')->label('Teams assigned'),
                TextColumn::make('created_at')->dateTime(),
                IconColumn::make('is_confirmed')->boolean(),

            ])
            ->filters([

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
