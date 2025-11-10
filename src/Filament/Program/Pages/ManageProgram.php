<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Program\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Schema;

class ManageProgram extends EditTenantProfile
{
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-document-text';

    public static function getLabel(): string
    {
        $programTypeName = config('filament-team-management.models.program')::getModelNameLower();

        return 'Manage '.ucfirst($programTypeName);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label('Enter a name for the team'),
            ]);
    }
}
