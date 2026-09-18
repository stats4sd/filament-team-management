<?php

namespace Stats4sd\FilamentTeamManagement\Tests\Fixtures\Policies;

use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\HostUser;

class UserPolicy
{
    public function viewAny(HostUser $actor): bool
    {
        return (bool) $actor->host_admin;
    }

    public function view(HostUser $actor, Model $user): bool
    {
        return (bool) $actor->host_admin;
    }

    public function create(HostUser $actor): bool
    {
        return (bool) $actor->host_admin;
    }

    public function update(HostUser $actor, Model $user): bool
    {
        return (bool) $actor->host_admin;
    }

    public function delete(HostUser $actor, Model $user): bool
    {
        return (bool) $actor->host_admin;
    }

    public function deleteAny(HostUser $actor): bool
    {
        return (bool) $actor->host_admin;
    }
}
