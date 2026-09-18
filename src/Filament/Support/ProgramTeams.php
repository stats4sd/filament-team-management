<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Support;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Actions\CreateTeam;
use Stats4sd\FilamentTeamManagement\Actions\LinkTeamToProgram;
use Stats4sd\FilamentTeamManagement\Actions\MembershipBatch;
use Stats4sd\FilamentTeamManagement\Actions\UnlinkTeamFromProgram;
use Stats4sd\FilamentTeamManagement\Actions\UpdateTeam;

class ProgramTeams
{
    public static function configure(Table $table, Closure $program): Table
    {
        abort_unless(config('filament-team-management.use_programs'), 403);
        Access::authorize('viewTeams', $program());

        return $table->relationship(fn () => $program()->teams())->inverseRelationship('programs')
            ->modifyQueryUsing(fn ($query) => Access::visibleQuery($query))
            ->columns([TextColumn::make('name')->searchable(), TextColumn::make('description')])
            ->headerActions([
                Action::make('attach')->label('Link existing ' . config('filament-team-management.names.team'))->schema([
                    Select::make('recordId')->label(ucfirst(config('filament-team-management.names.team')))->multiple()->required()->options(fn () => config('filament-team-management.models.team')::all()->filter(fn (Model $team) => Access::allows('view', $team) && Access::allows('linkTeam', [$program(), $team]) && Access::allows('linkProgram', [$team, $program()]))->pluck('name', 'id')),
                ])->action(function (array $data) use ($program) {
                    $records = config('filament-team-management.models.team')::whereKey($data['recordId'])->get();
                    abort_unless($records->count() === count(array_unique($data['recordId'])), 403);
                    foreach ($records as $record) {
                        Access::authorize('view', $record);
                    }
                    app(MembershipBatch::class)->handle(Access::actor(), 'link_team', $records, $program());
                }),
                Action::make('create')->label('Create ' . config('filament-team-management.names.team'))
                    ->authorize(fn () => Access::allows('create', config('filament-team-management.models.team')))
                    ->schema([TextInput::make('name')->required()->maxLength(255), Textarea::make('description')])
                    ->action(fn (array $data) => Access::actor()->getConnection()->transaction(function () use ($data, $program) {
                        $team = app(CreateTeam::class)->handle(Access::actor(), $data);
                        app(LinkTeamToProgram::class)->handle(Access::actor(), $program(), $team);
                    })),
            ])
            ->recordActions([
                Action::make('edit')->authorize(fn (Model $record) => Access::allows('update', $record))->fillForm(fn (Model $record) => $record->only(['name', 'description']))->schema([TextInput::make('name')->required()->maxLength(255), Textarea::make('description')])
                    ->action(fn (Model $record, array $data) => app(UpdateTeam::class)->handle(Access::actor(), $record, $data)),
                Action::make('detach')->label('Unlink')->requiresConfirmation()->authorize(fn (Model $record) => Access::allows('unlinkTeam', [$program(), $record]) && Access::allows('unlinkProgram', [$record, $program()]))
                    ->action(fn (Model $record) => app(UnlinkTeamFromProgram::class)->handle(Access::actor(), $program(), $record)),
            ])
            ->toolbarActions([BulkAction::make('detach')->label('Unlink selected')->requiresConfirmation()->action(fn ($records) => app(MembershipBatch::class)->handle(Access::actor(), 'unlink_team', $records, $program()))]);
    }
}
