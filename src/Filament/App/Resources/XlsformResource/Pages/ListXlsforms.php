<?php

namespace Stats4sd\FilamentTeamManagement\Filament\App\Resources\XlsformResource\Pages;

use App\Filament\App\Resources\XlsformResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListXlsforms extends ListRecords
{
    protected static string $resource = XlsformResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
