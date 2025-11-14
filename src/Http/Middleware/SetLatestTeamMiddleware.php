<?php

namespace Stats4sd\FilamentTeamManagement\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Stats4sd\FilamentTeamManagement\Models\User;
use Symfony\Component\HttpFoundation\Response;

class SetLatestTeamMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // check the user has a latestTeam method
        if (! method_exists($user, 'latestTeam')) {
            return $next($request);
        }

        if (! Filament::getTenant()) {
            return $next($request);
        }

        $user->latestTeam()->associate(Filament::getTenant())->save();

        return $next($request);
    }
}
