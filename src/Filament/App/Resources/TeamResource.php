<?php

namespace Stats4sd\FilamentTeamManagement\Filament\App\Resources;

use Stats4sd\FilamentTeamManagement\Filament\App\Resources\TeamResource\Pages;
use Stats4sd\FilamentTeamManagement\Filament\App\Resources\TeamResource\RelationManagers;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TeamResource extends Resource
{
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $model = Team::class;

    // when user click on sidebar item, it shows the view page of the selected team directly
    public static function getNavigationItems(): array
    {
        logger('submodule');

        return [
            NavigationItem::make()
                ->label(__('My Team'))
                ->icon('heroicon-o-home')
                ->group('Settings')
                ->url(self::getUrl('view', ['record' => Filament::getTenant()])),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Team Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('website')
                            ->url()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('website')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('# Users')
                    ->counts('users')
                    ->sortable(),
                Tables\Columns\TextColumn::make('invites_count')
                    ->label('# Invites')
                    ->counts('invites')
                    ->sortable(),
                Tables\Columns\TextColumn::make('xlsforms_count')
                    ->label('# Xlsforms')
                    ->counts('xlsforms')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->sortable(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\UsersRelationManager::class,
            RelationManagers\InvitesRelationManager::class,
            RelationManagers\XlsformsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'edit' => Pages\EditTeam::route('/{record}/edit'),
            'view' => Pages\ViewTeam::route('/{record}'),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('description')->hiddenLabel(),
            ]);
    }
}
