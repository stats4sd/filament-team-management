<?php

namespace Stats4sd\FilamentTeamManagement\Http\Responses;

use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;

class RegisterResponse implements RegistrationResponse
{
    /**
     * @return mixed
     */
    public function toResponse($request)
    {
        // always redirect user to app panel, as app panel is the only entry point of this application.
        // admin user can go to admin panel via Admin panel menu item in sidebar

        $homeUrl = Filament::getHomeUrl();

        return redirect()->intended($homeUrl);
    }
}
