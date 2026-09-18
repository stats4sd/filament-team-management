<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([TextInput::make('name')->required()->maxLength(255), TextInput::make('email')->email()->required()->unique(ignoreRecord: true)]);
    }
}
