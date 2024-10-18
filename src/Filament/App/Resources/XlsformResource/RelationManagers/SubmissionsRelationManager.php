<?php

namespace Stats4sd\FilamentTeamManagement\Filament\App\Resources\XlsformResource\RelationManagers;

use App\Models\LookupTables\Enumerator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use ValentinMorice\FilamentJsonColumn\FilamentJsonColumn;

class SubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'submissions';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // odk_id is for identification purpose, disable it to avoid user updating it accidentally
                Forms\Components\TextInput::make('odk_id')
                    ->required()
                    ->maxLength(255)
                    ->disabled()
                    ->columnSpanFull(),
                // hide viewer and show editor, to reduce number of clicks required
                FilamentJsonColumn::make('content')
                    ->columnSpanFull()
                    ->editorOnly()
                    ->editorHeight(500),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('')
            ->emptyStateDescription('Submissions will appear here once they have been submitted through ODK Collct and synced with the server.')
            ->recordTitleAttribute('odk_id')
            ->columns([
                Tables\Columns\TextColumn::make('odk_id'),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->sortable(),

                // comment below code temporary, customisation is required for HOLPA

                //  Tables\Columns\TextColumn::make('enumerator')
                //  ->getStateUsing(function ($record) {
                //      $enumeratorId = $record->content['survey_start']['inquirer_choice'];
                //      if($enumeratorId === "77") {
                //          return $record->content['survey_start']['inquirer_text'];
                //      }
                //      return Enumerator::firstWhere('name', $record->content['survey_start']['inquirer_choice'])->label ?? '~not found~';
                //  }),
                //  Tables\Columns\TextColumn::make('farm_name')
                //      ->getStateUsing(function ($record) {
                //          return $record->content['reg']['farm_name'];
                //      }),
                //  Tables\Columns\TextColumn::make('respondent_available')
                //      ->getStateUsing(function ($record) {
                //          return $record->content['reg']['respondent_check']['respondent_available'];
                //      }),
                //  Tables\Columns\TextColumn::make('consent')
                //      ->getStateUsing(function ($record) {
                //          return $record->content['consent_grp']['consent'] === "1" ? "Yes" : "No";
                //      }),
            ])
            ->filters([
                //
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\EditAction::make()->label('Edit'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
