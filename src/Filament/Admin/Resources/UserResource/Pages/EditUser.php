<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\UserResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\UserResource;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // encrypt password before saving it to database
        $data['password'] = bcrypt('password');

        // unset team_id, as it's not a field on the user model.
        // The relationship is handled because the field has the ->relationship() method.
        unset($data['team_id']);

        return $data;
    }
}
