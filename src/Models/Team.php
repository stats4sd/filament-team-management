<?php

namespace Stats4sd\FilamentTeamManagement\Models;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stats4sd\FilamentTeamManagement\Mail\InviteUser;
use Stats4sd\FilamentTeamManagement\Mail\AddUserToTeam;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Stats4sd\FilamentTeamManagement\Models\Interfaces\TeamInterface;
use Stats4sd\FilamentTeamManagement\Models\Traits\HasModelNameLowerString;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 */
class Team extends Model implements TeamInterface
{
    use HasModelNameLowerString;

    protected $table = 'teams';

    protected $guarded = ['id'];

    // ****** TEAM MANAGEMENT STUFF ******

    /**
     * Generate an invitation to join this team for each of the provided email addresses
     */
    public function sendInvites(array $emails): void
    {
        foreach ($emails as $email) {

            // if email is empty, skip to next email
            if ($email == null || $email == '') {
                continue;
            }

            // check if email address belong to any registered user
            $user = User::where('email', $email)->first();

            // email address does not belong to any registered user
            if (! $user) {
                $invite = $this->invites()->create([
                    'email' => $email,
                    'inviter_id' => auth()->id(),
                    'token' => Str::random(24),
                ]);

                Mail::to($invite->email)->send(new InviteUser($invite));

                // show notification after sending invitation email to user
                Notification::make()
                    ->success()
                    ->title('Invitation Sent')
                    ->body('An email invitation has been successfully sent to ' . $email)
                    ->send();

            // email address belongs to a registered user
            } else {
                // add user to team if user does not belong to this team yet
                if ($this->users->contains($user)) {
                    // show notification
                    Notification::make()
                        ->success()
                        ->title('User already in this team')
                        ->body('User ' . $email . ' belongs to this team already')
                        ->send();
                } else {
                    // add invites model for future tracing
                    $invite = $this->invites()->create([
                        'email' => $email,
                        'inviter_id' => auth()->id(),
                        config('filament-team-management.models.team')::getModelNameLower() . '_id' => $this->id,
                        'token' => 'na',
                        'is_confirmed' => true,
                    ]);

                    // add user to this team
                    $this->members()->attach($user);

                    // show notification 
                    Notification::make()
                        ->success()
                        ->title('User added')
                        ->body('User ' . $email . ' has been added to this ' . config('filament-team-management.models.team')::getModelNameLower())
                        ->send();

                    // send email notification to inform user that he/she has been added to a team
                    Mail::to($invite->email)->send(new AddUserToTeam($invite));

                    // show notification after sending email notification to user
                    Notification::make()
                        ->success()
                        ->title('Email Notification Sent')
                        ->body('An email notification has been successfully sent to ' . $email)
                        ->send();
                }

            }

        }
    }

    /** @return HasMany<Invite, $this> */
    public function invites(): HasMany
    {
        return $this->hasMany(
            Invite::class,
            foreignKey: static::getModelNameLower() . '_id',
            localKey: 'id'
        );
    }

    /** @return BelongsToMany<Model, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            related: config('filament-team-management.models.user'),
            table: static::getModelNameLower() . '_members',
            foreignPivotKey: static::getModelNameLower() . '_id',
            relatedPivotKey: config('filament-team-management.models.user')::getModelNameLower() . '_id'
        )
            ->withPivot('is_admin');
    }

    /** @return BelongsToMany<Model, $this> */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(
            config('filament-team-management.models.user'),
            static::getModelNameLower() . '_members',
            static::getModelNameLower() . '_id',
            config('filament-team-management.models.user')::getModelNameLower() . '_id'
        )
            ->withPivot('is_admin')
            ->wherePivot('is_admin', 1);
    }

    /** @return BelongsToMany<Model, $this> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            config('filament-team-management.models.user'),
            static::getModelNameLower() . '_members',
            static::getModelNameLower() . '_id',
            config('filament-team-management.models.user')::getModelNameLower() . '_id'
        )
            ->withPivot('is_admin')
            ->wherePivot('is_admin', 0);
    }

    /** @return BelongsToMany<Model, $this> */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(
            config('filament-team-management.models.program'),
            config('filament-team-management.models.program')::getModelNameLower() . '_' . static::getModelNameLower(),
            static::getModelNameLower() . '_id',
            config('filament-team-management.models.program')::getModelNameLower() . '_id'
        );
    }

    // add relationship to refer to team model itself, so that app panel > Teams resource can show the selected team for editing
    /** @return HasOne<self, $this> */
    public function team(): HasOne
    {
        return $this->hasOne(Team::class, 'id');
    }
}
