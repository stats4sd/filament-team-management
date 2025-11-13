<?php

namespace Stats4sd\FilamentTeamManagement\Http\Middleware;

use Filament\Exceptions\NoDefaultPanelSetException;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;

class AuthenticateThroughDefaultPanel extends Authenticate
{
    // Override the redirectTo method to always redirect to the default panel's login URL, instead of trying to use the current panel.
    /**
     * @throws NoDefaultPanelSetException
     */
    protected function redirectTo($request): ?string
    {
        return Filament::getDefaultPanel()->getLoginUrl();
    }
}
