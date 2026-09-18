<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Traits;

use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Stats4sd\FilamentTeamManagement\Filament\Support\Access;
use UnitEnum;

trait AuthorizesHostResource
{
    public static function getModelLabel(): string
    {
        foreach (['team', 'program', 'user'] as $type) {
            if (static::getModel() === config('filament-team-management.models.' . $type)) {
                return config('filament-team-management.names.' . $type);
            }
        }

        return parent::getModelLabel();
    }

    public static function getPluralModelLabel(): string
    {
        return Str::plural(static::getModelLabel());
    }

    public static function getAuthorizationResponse(string | UnitEnum $action, ?Model $record = null): Response
    {
        if (static::getModel() === config('filament-team-management.models.program') && ! config('filament-team-management.use_programs')) {
            return Response::deny();
        }

        return Gate::forUser(Access::actor())->inspect($action, $record ?? static::getModel());
    }

    public static function getEloquentQuery(): Builder
    {
        abort_if(static::getModel() === config('filament-team-management.models.program') && ! config('filament-team-management.use_programs'), 403);
        Access::authorize('viewAny', static::getModel());

        return Access::visibleQuery(parent::getEloquentQuery());
    }
}
