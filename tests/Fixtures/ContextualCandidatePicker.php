<?php

namespace Stats4sd\FilamentTeamManagement\Tests\Fixtures;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Contracts\UserPicker;

class ContextualCandidatePicker implements UserPicker
{
    public array $selections = [];

    public array $contexts = [];

    public function allow(Authenticatable $actor, Model $target, array $ids): void
    {
        $this->selections[$actor->getAuthIdentifier()][$target::class . ':' . $target->getKey()] = $ids;
    }

    public function query(Authenticatable $actor, Model $target): Builder
    {
        $this->contexts[] = [$actor, $target];

        return config('filament-team-management.models.user')::query()
            ->whereKey($this->selections[$actor->getAuthIdentifier()][$target::class . ':' . $target->getKey()] ?? [])
            ->where('email', 'like', '%@eligible.test');
    }
}
