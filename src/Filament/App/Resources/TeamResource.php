<?php

namespace Stats4sd\FilamentTeamManagement\Filament\App\Resources;

use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Stats4sd\FilamentTeamManagement\Filament\App\Resources\TeamResource\Pages;
use Stats4sd\FilamentTeamManagement\Filament\App\Resources\TeamResource\RelationManagers;

class TeamResource extends Resource
{
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Settings';

    public static function getModel(): string
    {
        return config('filament-team-management.models.team');
    }

    // when user click on sidebar item, it shows the view page of the selected team directly
    //
    // Note: after specifying below navigation item, it looks like...
    // admin panel > Teams resource > user relation manager is showing as app panel > Team resource > user relation manager
    // In user relation manager, a team member can invite user or add existing user to a team.
    // (in staging env with filament v3.2.115)
    //
    // In local env with filament v3.2.119, app  panel > Teams resource > user relation manager is used.
    // Not quite sure if it is the difference because of different filament version...
    //
    // By the way, a team member should not be able to invite new team member or add existing user as team member.
    // This should be done by program admin.
    public static function getNavigationItems(): array
    {
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
