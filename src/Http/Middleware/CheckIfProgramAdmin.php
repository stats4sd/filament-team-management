<?php

namespace Stats4sd\FilamentTeamManagement\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIfProgramAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the user has permission to access program admin panel
        if (! auth()->user()->can('access program admin panel')) {
            abort(403, 'Only program admin can access this page');
        }

        return $next($request);
    }
}
