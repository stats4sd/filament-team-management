<?php

namespace Stats4sd\FilamentTeamManagement\Models;

use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Notifications\Notification;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;
use Stats4sd\FilamentTeamManagement\Mail\InviteUser;
use Stats4sd\FilamentTeamManagement\Models\Interfaces\ProgramInterface;
use Stats4sd\FilamentTeamManagement\Models\Interfaces\TeamInterface;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $remember_token
 */
class User extends Authenticatable implements FilamentUser, HasDefaultTenant, HasTenants
{
    use HasRoles;
    use Notifiable;

    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ****** TEAM MANAGEMENT STUFF ******

    /**
     * Generate an invitation to be a role for each of the provided email addresses
     */
    public function sendInvites(array $items): void
    {
        foreach ($items as $item) {
            // if email is empty, skip to next email
            if ($item['email'] == null || $item['email'] == '') {
                continue;
            }

            /** @var RoleInvite $invite */
            $invite = $this->invites()->create([
                'email' => $item['email'],
                'role_id' => $item['role'],
                'token' => Str::random(24),
            ]);

            Mail::to($invite->email)->send(new InviteUser($invite));

            // show notification after sending invitation email to user
            Notification::make()
                ->success()
                ->title('Invitation Sent')
                ->body('An email invitation has been successfully sent to ' . $item['email'])
                ->send();
        }
    }

    public function invites(): HasMany
    {
        return $this->hasMany(RoleInvite::class, 'inviter_id');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(config('filament-team-management.models.team'), 'team_members', 'user_id', 'team_id')->withPivot('is_admin');
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(config('filament-team-management.models.program'), 'program_user', 'user_id', 'program_id');
    }

    public function belongsToTeam(TeamInterface $team): bool
    {
        return $this->teams->contains($team);
    }

    public function belongsToProgram(ProgramInterface $program): bool
    {
        return $this->programs->contains($program);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('Super Admin');
    }

    // ****** FILAMENT PANEL STUFF ******

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    // ****** MULTI-TENANCY STUFF ******

    // Admin users can access all teams
    public function canAccessTenant(Model $tenant): bool
    {
        // add different handling for different panel
        if ($tenant instanceof (config('filament-team-management.models.team'))) {

            // app panel
            // check permission
            if ($this->can('view all teams')) {
                return true;
            }

            // check if user belong to this team
            if ($this->teams->contains($tenant)) {
                return true;
            }

            $allAccessibleTeams = $this->getAllAccessibleTeams();

            if ($allAccessibleTeams->contains($tenant)) {
                return true;
            }

            // user cannot access this team
            return false;
        } elseif ($tenant instanceof (config('filament-team-management.models.program'))) {
            // program admin panel
            // check permission
            if ($this->can('view all programs')) {
                return true;
            }

            // check if user belong to this program
            if ($this->programs->contains($tenant)) {
                return true;
            }

            // user cannot access this team
            return false;
        }

        return false;
    }

    public function getTenants(Panel $panel): array | Collection
    {
        // add different handling for different panel
        if ($panel->getTenantModel() === config('filament-team-management.models.team')) {
            // app panel
            if ($this->can('view all teams')) {
                return config('filament-team-management.models.team')::all();
            } else {
                // find all accessible Team models
                return $this->getAllAccessibleTeams();
            }
        } else {
            // program admin panel
            return $this->can('view all programs') ? config('filament-team-management.models.program')::all() : $this->programs;
        }
    }

    public function getAllAccessibleTeams(): Collection
    {
        $allAccessibleTeams = collect();

        /** @var Program $program */
        foreach ($this->programs as $program) {
            foreach ($program->teams as $team) {
                $allAccessibleTeams->push($team);
            }
        }

        // find all teams belong to user
        foreach ($this->teams as $team) {
            $allAccessibleTeams->push($team);
        }

        // return $allAccessibleTeams;

        // when return $allAccessibleTeams directly, the collection does not contain the tenant. Not sure the root cause.
        // to make it work, get Team models in the same way temporary
        return Team::whereIn('id', $allAccessibleTeams->pluck('id'))->get();
    }

    // The last team the user was on.
    public function latestTeam(): BelongsTo
    {
        return $this->belongsTo(config('filament-team-management.models.team'), 'latest_team_id');
    }

    // The last program the user was on.
    public function latestProgram(): BelongsTo
    {
        return $this->belongsTo(config('filament-team-management.models.program'), 'latest_program_id');
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        // add different handling for different tenant
        $latestTenant = null;
        if ($panel->isDefault()) {
            // app panel
            $latestTenant = $this->latestTeam;
        } else {
            // program admin panel
            $latestTenant = $this->latestProgram;
        }

        return $latestTenant ?? $this->getTenants(Filament::getCurrentPanel())->first();
    }
}
