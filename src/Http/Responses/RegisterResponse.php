<?php

namespace Stats4sd\FilamentTeamManagement\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;
use Symfony\Component\HttpFoundation\Response;

class RegisterResponse implements RegistrationResponse
{
    public function toResponse($request): Response | RedirectResponse | Redirector
    {
        // always redirect user to app panel, as app panel is the only entry point of this application.
        // admin user can go to admin panel via Admin panel menu item in sidebar

        $homeUrl = Filament::getHomeUrl();

        return redirect()->intended($homeUrl);
    }
}
