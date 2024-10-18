<?php

namespace Stats4sd\FilamentTeamManagement\Http\Responses;

use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;

class RegisterResponse implements RegistrationResponse
{
    /**
     * @param  $request
     * @return mixed
     */
    public function toResponse($request)
    {
        // redirect user to admin panel (for admin user) or app panel (for non admin user) after user registration
        // $home = auth()->user()->is_admin ? '/admin' : '/app';

        // always redirect user to app panel, as app panel is the only entry point of this application.
        // admin user can go to admin panel via Admin panel menu item in sidebar
        $home = '/app';

        return redirect()->intended($home);
    }
}
