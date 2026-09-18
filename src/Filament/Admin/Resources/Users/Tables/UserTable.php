<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Users\Tables;

use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Stats4sd\FilamentTeamManagement\Actions\MembershipBatch;
use Stats4sd\FilamentTeamManagement\Actions\UpdateUser;
use Stats4sd\FilamentTeamManagement\Filament\Support\Access;

class UserTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('email')->searchable(),
            TextColumn::make('teams.name')->label(ucfirst(Str::plural(config('filament-team-management.names.team'))))->state(fn (Model $record) => $record->teams()->get()->filter(fn (Model $team) => Access::allows('view', $team))->pluck('name')->all())->badge(),
            TextColumn::make('programs.name')->label(ucfirst(Str::plural(config('filament-team-management.names.program'))))->visible(config('filament-team-management.use_programs'))->state(fn (Model $record) => config('filament-team-management.use_programs') ? $record->programs()->get()->filter(fn (Model $program) => Access::allows('view', $program))->pluck('name')->all() : [])->badge()])
            ->recordActions([EditAction::make()->using(fn (Model $record, array $data) => app(UpdateUser::class)->handle(Access::actor(), $record, $data))])
            ->toolbarActions([BulkAction::make('delete')->label('Delete selected')->requiresConfirmation()->authorize(fn () => Access::allows('deleteAny', config('filament-team-management.models.user')))->action(fn ($records) => app(MembershipBatch::class)->handle(Access::actor(), 'delete_user', $records))]);
    }
}
