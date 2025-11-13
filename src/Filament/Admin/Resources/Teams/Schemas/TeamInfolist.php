<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TeamInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('description')->hiddenLabel(),
            ]);
    }
}
