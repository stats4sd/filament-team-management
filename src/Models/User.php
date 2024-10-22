<?php

namespace Stats4sd\FilamentTeamManagement\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Notifications\Notification;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;
use Stats4sd\FilamentTeamManagement\Mail\InviteUser;

class User extends Authenticatable implements FilamentUser, HasDefaultTenant, HasTenants
{
    use HasFactory;
    use Notifiable;
    use HasRoles;

    /**
     * The attributes that a
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

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
        return $this->belongsToMany(Team::class, 'team_members')->withPivot('is_admin');
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class);
    }

    public function belongsToTeam(Team $team): bool
    {
        return $this->teams->contains($team);
    }

    public function belongsToProgram(Program $program): bool
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
        if ($tenant->getTable() == 'teams') {
            // app panel
            // check permission
            if ($this->can('view all teams')) {
                return true;
            }

            // check if user belong to this team
            if ($this->teams->contains($tenant)) {
                return true;
            }

            // check if this team belong to any program belong to user
            $allAccessibleTeams = Team::whereIn('id', $this->getAllAccessibleTeamIds())->get();
            if ($allAccessibleTeams->contains($tenant)) {
                return true;
            }

            // user cannot access this team
            return false;
        } elseif ($tenant->getTable() == 'programs') {
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
    }

    public function getTenants(Panel $panel): array | Collection
    {
        // add different handling for different panel
        if ($panel->isDefault()) {
            // app panel
            if ($this->can('view all teams')) {
                return Team::all();
            } else {
                // find all accessible Team models
                $allAccessibleTeams = Team::whereIn('id', $this->getAllAccessibleTeamIds())->get();

                return $allAccessibleTeams;
            }
        } else {
            // program admin panel
            return $this->can('view all programs') ? Program::all() : $this->programs;
        }
    }

    // to be revised, may not require after using Program model as program admin panel multi-tenancy
    public function getAllAccessibleTeamIds(): array
    {
        // find all teams belong to all programs of user
        $allTeamsIdInPrograms = [];

        foreach ($this->programs as $program) {
            $allPrograms = $program->teams->pluck('id');
            array_push($allTeamsIdInPrograms, $allPrograms);
        }

        // flatten array
        $allTeamsIdInPrograms = Arr::flatten($allTeamsIdInPrograms);

        // find all teams belong to user
        $allTeamIds = $this->teams->pluck('id');

        // all accessible teams = all teams belong to all programs of user + all teams belong to user
        $allAccessibleTeamIds = [];
        array_push($allAccessibleTeamIds, Arr::flatten($allTeamsIdInPrograms), Arr::flatten($allTeamIds));

        return Arr::flatten($allAccessibleTeamIds);
    }

    // The last team the user was on.
    public function latestTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'latest_team_id');
    }

    // The last program the user was on.
    public function latestProgram(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'latest_program_id');
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        // add different handling for different tenant
        if ($panel->isDefault()) {
            // app panel
            return $this->latestTeam;
        } else {
            // program admin panel
            return $this->latestProgram;
        }
    }
}
