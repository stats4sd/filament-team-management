<?php

namespace Stats4sd\FilamentTeamManagement\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIfProgramAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the user has permission to access program admin panel
        abort_unless(
            auth()->check() && auth()->user()->can('access program admin panel'),
            403,
            'Only program admin can access this page'
        );

        return $next($request);
    }
}
