<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\DatasetResource\Pages;

use App\Filament\Admin\Resources\DatasetResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListDatasets extends ListRecords
{
    protected static string $resource = DatasetResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(),
            'lookup_tables' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('lookup_table', '=', 1)),
            'holpa_datasets' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('lookup_table', '=', 0)),

        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
