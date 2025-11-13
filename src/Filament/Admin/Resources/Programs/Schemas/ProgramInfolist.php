<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Programs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProgramInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('description')->hiddenLabel(),
            ]);
    }
}
