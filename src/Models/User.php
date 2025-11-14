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
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Stats4sd\FilamentTeamManagement\Mail\InviteUser;
use Stats4sd\FilamentTeamManagement\Models\Interfaces\ProgramInterface;
use Stats4sd\FilamentTeamManagement\Models\Interfaces\TeamInterface;
use Stats4sd\FilamentTeamManagement\Models\Traits\HasModelNameLowerString;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $remember_token
 */
class User extends Authenticatable implements FilamentUser, HasDefaultTenant, HasTenants
{
    use HasModelNameLowerString;

    // alias trait method roles() as spatieRoles()
    use HasRoles {
        roles as spatieRoles;
    }
    use Notifiable;

    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function getTable()
    {
        return config('filament-team-management.table_names.users');
    }

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

            // check if email address belong to any registered user
            $user = User::where('email', $item['email'])->first();

            // email address does not belong to any registered user
            if (! $user) {
                /** @var Invite $invite */
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

            } else {
                // add role to user if user does not have this role yet
                $role = Role::find($item['role']);

                if ($user->roles->contains($role)) {
                    // show notification
                    Notification::make()
                        ->success()
                        ->title('User already has this role')
                        ->body('User ' . $item['email'] . ' has ' . $role->name . ' role already')
                        ->send();
                } else {
                    // add role to user
                    // notification, email will be handled by ModelHasRole model event "created"
                    $user->roles()->attach($role);
                }
            }

        }
    }

    // define roles relationship to use custom class ModelHasRole, to capture model event when creating new model_has_roles record
    public function roles(): BelongsToMany
    {
        return $this->spatieRoles()->using(ModelHasRole::class);
    }

    public function invites(): HasMany
    {
        return $this->hasMany(Invite::class, 'inviter_id');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(
            config('filament-team-management.models.team'),
            config('filament-team-management.models.team')::getModelNameLower() . '_members',
            config('filament-team-management.models.user')::getModelNameLower() . '_id',
            config('filament-team-management.models.team')::getModelNameLower() . '_id',
        )->withPivot('is_admin');
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(
            config('filament-team-management.models.program'),
            config('filament-team-management.table_names.program_members'),
            config('filament-team-management.models.user')::getModelNameLower() . '_id',
            config('filament-team-management.models.program')::getModelNameLower() . '_id',
        );
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

            if ($allAccessibleTeams->pluck('id')->contains($tenant->getKey())) {
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
        if (! config('filament-team-management.use_programs')) {
            return $this->teams;
        }

        return $this->programs->pluck('teams')
            ->flatten()
            ->merge($this->teams)
            ->unique('id');
    }

    // The last team the user was on.
    public function latestTeam(): BelongsTo
    {
        return $this->belongsTo(
            config('filament-team-management.models.team'),
            foreignKey: 'latest_team_id'
        );
    }

    // The last program the user was on.
    public function latestProgram(): BelongsTo
    {
        return $this->belongsTo(
            config('filament-team-management.models.program'),
            foreignKey: 'latest_program_id'
        );
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        $latestTenant = null;

        // add different handling for different tenant
        if ($panel->getTenantModel() === config('filament-team-management.models.team')) {
            // app panel
            $latestTenant = $this->latestTeam;
        }

        if ($panel->getTenantModel() === config('filament-team-management.models.program')) {
            // program admin panel
            $latestTenant = $this->latestProgram;
        }

        return $latestTenant ?? $this->getTenants(Filament::getCurrentPanel())->first();
    }
}
