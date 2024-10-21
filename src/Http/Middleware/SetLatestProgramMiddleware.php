<?php

namespace Stats4sd\FilamentTeamManagement\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLatestProgramMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        auth()->user()->latestProgram()->associate(Filament::getTenant())->save();

        return $next($request);
    }
}
