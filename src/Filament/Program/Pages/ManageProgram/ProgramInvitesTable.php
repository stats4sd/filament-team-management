<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Program\Pages\ManageProgram;

use Filament\Actions\BulkActionGroup;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProgramInvitesTable
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

            ])
            ->filters([
                //
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
