<?php

namespace Stats4sd\FilamentTeamManagement\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Stats4sd\FilamentTeamManagement\Models\User;
use Symfony\Component\HttpFoundation\Response;

class SetLatestProgramMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            abort(500, 'User is not an instance of Stats4sd\FilamentTeamManagement\Models\User. Please make sure your user model extends the model provided by this package to use this middleware.');
        }

        $user->latestProgram()->associate(Filament::getTenant())->save();

        return $next($request);
    }
}
