<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Programs\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Stats4sd\FilamentTeamManagement\Actions\DeleteProgram;
use Stats4sd\FilamentTeamManagement\Actions\UpdateProgram;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Programs\ProgramResource;
use Stats4sd\FilamentTeamManagement\Filament\Support\Access;

class ViewProgram extends ViewRecord
{
    protected static string $resource = ProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->using(fn (Model $record, array $data) => app(UpdateProgram::class)->handle(Access::actor(), $record, $data)),
            Actions\DeleteAction::make()->using(fn (Model $record) => app(DeleteProgram::class)->handle(Access::actor(), $record))->modalDescription('Deleting this ' . config('filament-team-management.names.program') . ' is irreversible. Memberships and invitations will be removed. Associated ' . Str::plural(config('filament-team-management.names.team')) . ' remain.'),
        ];
    }
}
