<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Auth;

use Illuminate\Contracts\Support\Htmlable;

class Login extends \Filament\Auth\Pages\Login
{

    // Override subheading to remove the link to register.
    // By default, this package assumes the only way to register is by invitation.
    public function getSubheading(): string|Htmlable|null
    {
        return '';
    }
}
