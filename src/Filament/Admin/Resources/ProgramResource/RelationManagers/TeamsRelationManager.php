<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\ProgramResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Model;

class TeamsRelationManager extends RelationManager
{
    protected static string $relationship = 'teams';

    // customise relation manager title
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return Str::ucfirst(Str::plural(config('filament-team-management.names.team')));
    }

    // turn on Edit mode so that "Add Existing Team to program" button will be shown when viewing program record
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(Str::ucfirst(config('filament-team-management.names.team')) . ' Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('programs.name')
                    ->searchable()
                    ->badge()
                    ->color('success'),

                // Note: Here it is using filament-team-management Team model, which does not have xlsform relationship yet.
                // xlsform relationship is added in main repo Team model because it uses HasXlsForms trait.
                // I think we can compromise not to show number of xlsforms belongs to a team here.

                // Tables\Columns\TextColumn::make('xlsforms_count')
                //     ->label('# Xlsforms')
                //     ->counts('xlsforms')
                //     ->sortable(),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('# Users')
                    ->counts('users')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\AttachAction::make()
                    ->label('Add Existing ' . Str::ucfirst(config('filament-team-management.names.team')) . ' to program'),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->label('Remove ' . Str::ucfirst(config('filament-team-management.names.team')))
                    ->modalSubmitActionLabel('Remove ' . Str::ucfirst(config('filament-team-management.names.team')))
                    ->modalHeading('Remove ' . Str::ucfirst(config('filament-team-management.names.team')) . ' from Program'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()->label('Remove selected')
                        ->modalSubmitActionLabel('Remove Selected ' . Str::ucfirst(Str::plural(config('filament-team-management.names.team'))))
                        ->modalHeading('Remove Selected ' . Str::ucfirst(Str::plural(config('filament-team-management.names.team'))) . ' from Program'),
                ]),
            ]);
    }
}
