<?php

namespace Stats4sd\FilamentTeamManagement\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLatestProgramMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();
        $tenant = Filament::getTenant();
        $model = config('filament-team-management.models.program');
        if ($user && method_exists($user, 'latestProgram') && $tenant instanceof $model && config('filament-team-management.use_programs')) {
            $user->latestProgram()->associate($tenant);
            if ($user->isDirty('latest_program_id')) {
                $user->save();
            }
        }

        return $next($request);
    }
}
