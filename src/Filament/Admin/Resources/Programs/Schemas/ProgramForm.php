<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Programs\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProgramForm
{
    public static function configure(Schema $schema): Schema
    {
        $programTypeName = Str::of(config('filament-team-management.models.program')::getModelNameLower())->ucFirst();

        return $schema
            ->columns(1)
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description'),
                Textarea::make('note'),
            ]);
    }
}
