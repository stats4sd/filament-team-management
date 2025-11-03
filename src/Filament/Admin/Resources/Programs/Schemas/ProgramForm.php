<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Programs\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProgramForm
{
    public static function configure(Schema $schema): Schema
    {
        $programTypeName = \Illuminate\Support\Str::of(config('filament-team-management.models.program')::getModelNameLower())->ucFirst();

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
