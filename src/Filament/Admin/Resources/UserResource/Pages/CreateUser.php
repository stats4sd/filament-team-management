<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

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
