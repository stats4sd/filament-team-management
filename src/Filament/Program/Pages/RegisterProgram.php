<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Program\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Actions\CreateProgram;
use Stats4sd\FilamentTeamManagement\Filament\Support\Access;

class RegisterProgram extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Create ' . config('filament-team-management.names.program');
    }

    public static function canView(): bool
    {
        return config('filament-team-management.use_programs') && Access::allows('create', config('filament-team-management.models.program'));
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([TextInput::make('name')->label('Name')->required()->maxLength(255)]);
    }

    protected function handleRegistration(array $data): Model
    {
        return app(CreateProgram::class)->handle(Access::actor(), $data);
    }
}
