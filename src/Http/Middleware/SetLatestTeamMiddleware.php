<?php

namespace Stats4sd\FilamentTeamManagement\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLatestTeamMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();
        $tenant = Filament::getTenant();
        $model = config('filament-team-management.models.team');
        if ($user && method_exists($user, 'latestTeam') && $tenant instanceof $model) {
            $user->latestTeam()->associate($tenant);
            if ($user->isDirty('latest_team_id')) {
                $user->save();
            }
        }

        return $next($request);
    }
}
