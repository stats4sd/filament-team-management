<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Program\Pages\ManageProgram;

use Awcodes\Shout\Components\Shout;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Table;
use Filament\Schemas\Schema;

class ProgramProjectsTable
{

    // TODO: check how to use this form when "New project" button is clicked
    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description'),

            ]);
    }

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
                // TODO: add "New project" button to create new project with modal popup
                CreateAction::make(),
                AttachAction::make('Add Existing Projects')
                    ->recordTitleAttribute('name')
                    ->multiple(),

            ])
            ->recordActions([
                DetachAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
