<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Auth;

use Filament\Auth\Http\Responses\RegistrationResponse;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class RegisterResponse extends RegistrationResponse
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        return redirect()->to('/');
    }
}
