<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\UserResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // encrypt password before saving it to database
        $data['password'] = bcrypt('password');

        return $data;
    }
}
