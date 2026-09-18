<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Support;

use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Stats4sd\FilamentTeamManagement\Contracts\UserPicker;

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
        $picker = config('filament-team-management.user_picker');
        if (! $picker) {
            return config('filament-team-management.models.user')::query()->whereRaw('1 = 0');
        }
        $picker = app($picker);
        if (! $picker instanceof UserPicker) {
            throw new \LogicException('user_picker must implement UserPicker.');
        }

        return $picker->query(static::actor(), $target);
    }

    public static function visibleQuery(Builder $query): Builder
    {
        $ids = (clone $query)->get()->filter(fn (Model $record) => static::allows('view', $record))->modelKeys();

        return $query->whereKey($ids);
    }
}
