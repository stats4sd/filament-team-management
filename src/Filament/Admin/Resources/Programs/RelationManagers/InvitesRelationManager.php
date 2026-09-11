<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Programs\RelationManagers;

use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class InvitesRelationManager extends RelationManager
{
    protected static string $relationship = 'invites';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('program.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('inviter.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_confirmed')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            // Phase 3: invites are system-generated (via sendInvites() and
            // role-assignment tracing) and double as an audit log, so no
            // hand-authored Create/Edit here — only Delete. A proper "send
            // invite" UX lands in the Phase 3 consolidated invite flow.
            ->headerActions([
                //
            ])
            ->recordActions([
                DeleteAction::make(),
            ]);
    }
}
