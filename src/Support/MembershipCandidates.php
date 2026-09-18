<?php

namespace Stats4sd\FilamentTeamManagement\Support;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Contracts\UserPicker;
use Stats4sd\FilamentTeamManagement\Models\User;

final class MembershipCandidates
{
    /** @return Builder<User> */
    public function query(Authenticatable $actor, Model $target): Builder
    {
        $picker = config('filament-team-management.user_picker');
        if (! $picker) {
            return config('filament-team-management.models.user')::query()->whereRaw('1 = 0');
        }

        $picker = app($picker);
        if (! $picker instanceof UserPicker) {
            throw new \LogicException('user_picker must implement UserPicker.');
        }

        return $picker->query($actor, $target);
    }

    /**
     * @param  array<array-key, int|string>  $ids
     * @return Collection<int, User>
     */
    public function resolveSelection(Authenticatable $actor, Model $target, array $ids): Collection
    {
        $ids = array_unique($ids);
        $users = $this->query($actor, $target)->whereKey($ids)->get();

        if (array_diff($ids, $users->modelKeys()) !== []) {
            throw new AuthorizationException('The selected members are not available.');
        }

        return $users;
    }
}
