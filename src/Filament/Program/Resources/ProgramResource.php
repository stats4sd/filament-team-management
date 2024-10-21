<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Program\Resources;

use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Stats4sd\FilamentTeamManagement\Filament\Program\Resources\ProgramResource\Pages;
use Stats4sd\FilamentTeamManagement\Filament\Program\Resources\ProgramResource\RelationManagers;
use Stats4sd\FilamentTeamManagement\Models\Program;

class ProgramResource extends Resource
{
    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $model = Program::class;

    // when user click on sidebar item, it shows the view page of the selected program directly
    public static function getNavigationItems(): array
    {
        return [
            NavigationItem::make()
                ->label(__('My Program'))
                ->icon('heroicon-o-home')
                ->group('Settings')
                ->url(self::getUrl('view', ['record' => Filament::getTenant()])),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Program Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('description')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('note')
                            ->maxLength(255),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('description'),
                Tables\Columns\TextColumn::make('note'),
                Tables\Columns\TextColumn::make('teams_count')
                    ->label('# Teams')
                    ->counts('teams'),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('# Users')
                    ->counts('users'),
                Tables\Columns\TextColumn::make('invites_count')
                    ->label('# Invites')
                    ->counts('invites'),
                Tables\Columns\TextColumn::make('created_at'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TeamsRelationManager::class,
            RelationManagers\UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrograms::route('/'),
            'create' => Pages\CreateProgram::route('/create'),
            'edit' => Pages\EditProgram::route('/{record}/edit'),
            'view' => Pages\ViewProgram::route('/{record}'),
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
