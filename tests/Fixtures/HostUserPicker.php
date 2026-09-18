<?php

namespace Stats4sd\FilamentTeamManagement\Tests\Fixtures;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Contracts\UserPicker;

class HostUserPicker implements UserPicker
{
    public function query(Authenticatable $actor, Model $target): Builder
    {
        return config('filament-team-management.models.user')::query()->when(! $actor->host_admin, fn ($query) => $query->whereKey($actor->getAuthIdentifier()));
    }
}
