<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DatasetResource\Pages;
use App\Filament\Admin\Resources\DatasetResource\RelationManagers;

//use App\Models\Dataset;
use App\Services\HelperService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Stats4sd\FilamentOdkLink\Models\OdkLink\Dataset;

class DatasetResource extends Resource
{
    protected static ?string $model = Dataset::class;

    protected static ?string $navigationIcon = 'heroicon-o-code-bracket-square';

    public static function form(Form $form): Form
    {

        $models = HelperService::getModels()
            ->mapWithKeys(function ($model) {
                return [
                    $model => (new $model())->getTable()
                ];
            });


        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Name of the dataset'),
                Forms\Components\Select::make('entity_model')
                    ->label('Which Database table does this dataset represent?')
                    ->options($models),
                Forms\Components\Textarea::make('description')
                    ->label('Enter a brief description of the dataset')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Hidden::make('primary_key')
                    ->default('id'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('entity_model')
                    ->label('Database Table')
                    ->formatStateUsing(fn($state) => Str::of(collect(Str::ucsplit($state, '/'))->last())->lower()->plural()),
                Tables\Columns\TextColumn::make('variables_count')
                    ->label('# of Variables defined')
                    ->counts('variables'),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->recordUrl(fn($record) => static::getUrl('view', ['record' => $record]));
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\VariablesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDatasets::route('/'),
            'view' => Pages\ViewDataset::route('/{record}'),
        ];
    }
}
