<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Programs\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Stats4sd\FilamentTeamManagement\Actions\CreateProgram;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Programs\ProgramResource;
use Stats4sd\FilamentTeamManagement\Filament\Support\Access;

class ListPrograms extends ListRecords
{
    protected static string $resource = ProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->using(fn (array $data) => app(CreateProgram::class)->handle(Access::actor(), $data)),
        ];
    }
}
