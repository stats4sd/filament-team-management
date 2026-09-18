<?php

namespace Stats4sd\FilamentTeamManagement\Filament\App\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Actions\CreateTeam;
use Stats4sd\FilamentTeamManagement\Filament\Support\Access;

class RegisterTeam extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Create ' . config('filament-team-management.names.team');
    }

    public static function canView(): bool
    {
        return Access::allows('create', config('filament-team-management.models.team'));
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([TextInput::make('name')->label('Name')->required()->maxLength(255)]);
    }

    protected function handleRegistration(array $data): Model
    {
        return app(CreateTeam::class)->handle(Access::actor(), $data);
    }
}
