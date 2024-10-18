<?php

namespace Stats4sd\FilamentTeamManagement\Filament\App\Resources;

use App\Filament\App\Resources\XlsformResource\Pages;
use App\Filament\App\Resources\XlsformResource\RelationManagers;
use Filament\Forms\Form;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Stats4sd\FilamentOdkLink\Models\OdkLink\Xlsform;

class XlsformResource extends Resource
{
    protected static ?string $model = Xlsform::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $tenantOwnershipRelationshipName = 'owner';

    // do not show Xlsform resource in side bar
    protected static bool $shouldRegisterNavigation = false;

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                ViewEntry::make('submission_summary')
                    ->view('submission_summary_wrapper')
                    ->columnSpanFull()
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SubmissionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListXlsforms::route('/'),
            'view' => Pages\ViewXlsform::route('/{record}'),
            'monitor' => Pages\MonitorXlsform::route('/{record}/monitor'),
        ];
    }
}
