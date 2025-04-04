<?php

namespace Stats4sd\FilamentTeamManagement\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIfAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the user has permission to access admin panel
        if (! auth()->user()->can('access admin panel')) {
            abort(403, 'Only platform administrators can access this page');
        }

        return $next($request);
    }
}
