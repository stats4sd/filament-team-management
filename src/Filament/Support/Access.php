<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Support;

use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Stats4sd\FilamentTeamManagement\Support\MembershipCandidates;

class Access
{
    public static function actor(): Authenticatable
    {
        $actor = Filament::auth()->user();
        abort_unless($actor, 403);

        return $actor;
    }

    public static function allows(string $ability, mixed $arguments): bool
    {
        return Filament::auth()->check() && Gate::forUser(static::actor())->allows($ability, $arguments);
    }

    public static function authorize(string $ability, mixed $arguments): void
    {
        Gate::forUser(static::actor())->authorize($ability, $arguments);
    }

    public static function users(Model $target): Builder
    {
        return app(MembershipCandidates::class)->query(static::actor(), $target);
    }

    public static function visibleQuery(Builder $query): Builder
    {
        $ids = (clone $query)->get()->filter(fn (Model $record) => static::allows('view', $record))->modelKeys();

        return $query->whereKey($ids);
    }
}
