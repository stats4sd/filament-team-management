<?php

namespace Stats4sd\FilamentTeamManagement\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Stats4sd\FilamentTeamManagement\Models\Interfaces\ProgramInterface;
use Stats4sd\FilamentTeamManagement\Models\Interfaces\TeamInterface;
use Stats4sd\FilamentTeamManagement\Models\Traits\HasModelNameLowerString;
use Stats4sd\FilamentTeamManagement\Support\Membership;

/**
 * @property string $name
 * @property string $email
 * @property string $password
 */
class User extends Authenticatable implements FilamentUser, HasDefaultTenant, HasTenants
{
    use HasFactory;
    use HasModelNameLowerString;
    use Notifiable;

    protected $guarded = ['id'];

    protected $hidden = ['password', 'remember_token'];

    public function getTable()
    {
        return config('filament-team-management.table_names.users');
    }

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed'];
    }

    public function setEmailAttribute(string $email): void
    {
        $this->attributes['email'] = Membership::email($email);
    }

    public function invites(): HasMany
    {
        return $this->hasMany(Invite::class, 'inviter_id');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(
            config('filament-team-management.models.team'),
            config('filament-team-management.table_names.team_members'),
            config('filament-team-management.column_names.users_foreign_key'),
            config('filament-team-management.column_names.teams_foreign_key'),
        );
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(
            config('filament-team-management.models.program'),
            config('filament-team-management.table_names.program_members'),
            config('filament-team-management.column_names.users_foreign_key'),
            config('filament-team-management.column_names.programs_foreign_key'),
        );
    }

    public function belongsToTeam(Model & TeamInterface $team): bool
    {
        return $this->teams()->whereKey($team->getKey())->exists();
    }

    public function belongsToProgram(Model & ProgramInterface $program): bool
    {
        return config('filament-team-management.use_programs') && $this->programs()->whereKey($program->getKey())->exists();
    }

    // Hosts implement all three access methods from the same accessible query/rule.
    public function canAccessPanel(Panel $panel): bool
    {
        return false;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return false;
    }

    public function getTenants(Panel $panel): array | Collection
    {
        return collect();
    }

    public function latestTeam(): BelongsTo
    {
        return $this->belongsTo(config('filament-team-management.models.team'), 'latest_team_id');
    }

    public function latestProgram(): BelongsTo
    {
        return $this->belongsTo(config('filament-team-management.models.program'), 'latest_program_id');
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        $tenants = collect($this->getTenants($panel));
        $latest = match ($panel->getTenantModel()) {
            config('filament-team-management.models.team') => $this->getAttribute('latest_team_id'),
            config('filament-team-management.models.program') => config('filament-team-management.use_programs') ? $this->getAttribute('latest_program_id') : null,
            default => null,
        };

        return $tenants->first(fn (Model $tenant): bool => (string) $tenant->getKey() === (string) $latest) ?? $tenants->first();
    }
}
