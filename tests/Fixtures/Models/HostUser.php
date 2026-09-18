<?php

namespace Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models;

use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Stats4sd\FilamentTeamManagement\Database\Factories\UserFactory;
use Stats4sd\FilamentTeamManagement\Models\User;

class HostUser extends User
{
    protected static function newFactory(): UserFactory
    {
        return new class extends UserFactory
        {
            protected $model = HostUser::class;
        };
    }

    public function grant(Model $target, string $ability = '*'): void
    {
        DB::table('host_grants')->insertOrIgnore(['user_id' => $this->getKey(), 'target_type' => $target->getTable(), 'target_id' => $target->getKey(), 'ability' => $ability]);
    }

    public function allowed(Model $target, string $ability): bool
    {
        return (bool) $this->getAttribute('host_admin') || DB::table('host_grants')->where('user_id', $this->getKey())->where('target_type', $target->getTable())->where('target_id', $target->getKey())->whereIn('ability', ['*', $ability])->exists();
    }

    public function accessible(Panel $panel): ?Builder
    {
        $type = match ($panel->getTenantModel()) {
            config('filament-team-management.models.team') => 'team',
            config('filament-team-management.models.program') => config('filament-team-management.use_programs') ? 'program' : null,
            default => null,
        };
        if (! $type) {
            return null;
        }
        $model = config('filament-team-management.models.' . $type);
        $query = $model::query();
        if ($this->getAttribute('host_admin')) {
            return $query;
        }

        return $query->whereHas('users', fn (Builder $users) => $users->whereKey($this->getKey()));
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() !== config('filament-team-management.panels.admin') || (bool) $this->getAttribute('host_admin');
    }

    public function getTenants(Panel $panel): array | Collection
    {
        return $this->canAccessPanel($panel) ? ($this->accessible($panel)?->get() ?? collect()) : collect();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        $panel = Filament::getCurrentPanel();

        return $panel && $panel->getTenantModel() === $tenant::class && $this->canAccessPanel($panel) && ($this->accessible($panel)?->whereKey($tenant->getKey())->exists() ?? false);
    }
}
