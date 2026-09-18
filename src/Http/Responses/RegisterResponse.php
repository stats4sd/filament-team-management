<?php

namespace Stats4sd\FilamentTeamManagement\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;
use Stats4sd\FilamentTeamManagement\Filament\Support\MembershipNavigation;
use Symfony\Component\HttpFoundation\Response;

class RegisterResponse implements RegistrationResponse
{
    public function toResponse($request): Response | RedirectResponse | Redirector
    {
        $destination = Filament::auth()->check()
            ? MembershipNavigation::afterDeparture()
            : route('filament-team-management.no-memberships');

        return redirect($destination);
    }
}
