<?php

namespace Stats4sd\FilamentTeamManagement\Filament\Support;

use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\TeamResource;

class MembershipNavigation
{
    public static function afterDeparture(bool $adminContext = false, string $departedType = 'team'): string
    {
        $actor = Access::actor();
        if (! $actor instanceof FilamentUser || ! $actor instanceof HasTenants || ! $actor instanceof Model) {
            return route('filament-team-management.no-memberships');
        }
        $actor->refresh();
        Filament::setTenant(null);
        $previousPanel = Filament::getCurrentPanel();

        try {
            foreach ($departedType === 'program' ? ['program', 'app'] : ['app'] as $panelType) {
                $modelType = $panelType === 'program' ? 'program' : 'team';
                if ($modelType === 'program' && ! config('filament-team-management.use_programs')) {
                    continue;
                }
                $panel = Filament::getPanels()[config('filament-team-management.panels.' . $panelType)] ?? null;
                $model = config('filament-team-management.models.' . $modelType);
                if (! is_string($model) || ! $panel || $panel->getTenantModel() !== $model || ! $actor->canAccessPanel($panel)) {
                    continue;
                }
                Filament::setCurrentPanel($panel);
                $tenant = collect($actor->getTenants($panel))->first(fn (Model $tenant) => $tenant instanceof $model && $actor->canAccessTenant($tenant) && Access::allows('view', $tenant));
                if ($tenant) {
                    return $panel->getUrl($tenant);
                }
                if ($panel->hasTenantRegistration() && Access::allows('create', $model)) {
                    return $panel->getTenantRegistrationUrl();
                }
            }
        } finally {
            Filament::setCurrentPanel($previousPanel);
        }
        if ($adminContext) {
            $admin = Filament::getPanels()[config('filament-team-management.panels.admin')] ?? null;
            if ($admin && $actor->canAccessPanel($admin) && Access::allows('viewAny', config('filament-team-management.models.team'))) {
                return TeamResource::getUrl('index', panel: $admin->getId());
            }
        }

        return route(config('filament-team-management.no_memberships_route') ?: 'filament-team-management.no-memberships');
    }

    public static function programLinks(Model $team): string
    {
        $panel = Filament::getPanels()[config('filament-team-management.panels.program')] ?? null;
        $actor = Access::actor();
        if (! $actor instanceof FilamentUser || ! $actor instanceof HasTenants || ! $actor instanceof Model) {
            return route('filament-team-management.no-memberships');
        }
        $current = Filament::getCurrentPanel();

        try {
            if ($panel) {
                Filament::setCurrentPanel($panel);
            }

            return $team->programs->map(function (Model $program) use ($panel, $actor): string {
                $name = e($program->name);
                if ($panel && $actor->canAccessPanel($panel) && $actor->canAccessTenant($program) && Access::allows('view', $program)) {
                    return '<a class="underline" href="' . e($panel->getUrl($program)) . '">' . $name . '</a>';
                }

                return $name;
            })->join(', ');
        } finally {
            Filament::setCurrentPanel($current);
        }
    }
}
