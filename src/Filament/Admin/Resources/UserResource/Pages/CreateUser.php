<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\UserResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // encrypt the user-provided password before saving
        $data['password'] = bcrypt($data['password']);

        // team_id is a relationship field (Select->relationship('teams', ...)) but its field name
        // differs from the relationship name, so Filament doesn't strip it from the model data.
        // Remove it here so it isn't written as a column; saveRelationships() handles the sync.
        unset($data['team_id']);

        return $data;
    }
}
