<?php

namespace Stats4sd\FilamentTeamManagement\Http\Controllers;

use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class NoMembershipsController
{
    public function __invoke(): View | RedirectResponse
    {
        $panel = Filament::getDefaultPanel();
        if (! auth()->guard($panel->getAuthGuard())->check()) {
            return redirect()->guest($panel->getLoginUrl());
        }

        return view('filament-team-management::no-memberships');
    }
}
