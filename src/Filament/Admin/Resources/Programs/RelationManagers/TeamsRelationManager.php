<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Programs\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TeamsRelationManager extends RelationManager
{
    protected static string $relationship = 'teams';

    // customise relation manager title
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return Str::ucfirst(Str::plural(config('filament-team-management.table_names.teams')));
    }

    // turn on Edit mode so that "Add Existing Team to program" button will be shown when viewing program record
    public function isReadOnly(): bool
    {
        return false;
    }

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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
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
                CreateAction::make(),
                AttachAction::make()
                    ->label('Add Existing ' . config('filament-team-management.models.team')::getModelNameLower() . ' to program'),
            ])
            ->recordActions([
                DetachAction::make()
                    ->label('Remove ' . config('filament-team-management.models.team')::getModelNameLower())
                    ->modalSubmitActionLabel('Remove ' . config('filament-team-management.models.team')::getModelNameLower())
                    ->modalHeading('Remove ' . config('filament-team-management.models.team')::getModelNameLower() . ' from Program'),
            ])
            ->groupedBulkActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()->label('Remove selected')
                        ->modalSubmitActionLabel('Remove Selected ' . Str::ucfirst(Str::plural(config('filament-team-management.table_names.teams'))))
                        ->modalHeading('Remove Selected ' . Str::ucfirst(Str::plural(config('filament-team-management.table_names.teams'))) . ' from Program'),
                ]),
            ]);
    }
}
