<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Program\Pages\ManageProgram;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\Schemas\TeamForm;

class ProgramProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->relationship(fn () => Filament::getTenant()->teams())
            ->inverseRelationship('programs')
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('users_count')
                    ->label('# Users')
                    ->counts('users'),
                TextColumn::make('created_at'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->schema(TeamForm::getFormSchema()),

                AttachAction::make('Add Existing Projects')
                    ->recordTitleAttribute('name')
                    ->multiple(),

            ])
            ->recordActions([
                DetachAction::make(),
                EditAction::make()
                    ->schema(TeamForm::getFormSchema()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
