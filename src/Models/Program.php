<?php

namespace Stats4sd\FilamentTeamManagement\Models;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Stats4sd\FilamentTeamManagement\Mail\InviteUser;
use Stats4sd\FilamentTeamManagement\Mail\UpdateUser;
use Stats4sd\FilamentTeamManagement\Models\Interfaces\ProgramInterface;
use Stats4sd\FilamentTeamManagement\Models\Traits\HasModelNameLowerString;

/**
 * @property string $name
 * @property string $description
 * @property string $note
 */
class Program extends Model implements ProgramInterface
{
    use HasModelNameLowerString;

    public function getTable()
    {
        return config('filament-team-management.table_names.programs');
    }

    protected $guarded = ['id'];

    // ****** TEAM MANAGEMENT STUFF ******

    /**
     * Generate an invitation to join this program for each of the provided email addresses
     */
    public function sendInvites(array $emails): void
    {
        $programAdminRole = Role::where('name', 'Program Admin')->first();

        foreach ($emails as $email) {
            // if email is empty, skip to next email
            if ($email == null || $email == '') {
                continue;
            }

            // check if email address belong to any registered user
            $user = User::where('email', $email)->first();

            // email address does not belong to any registered user
            if (! $user) {

                /** @var Invite $invite */
                $invite = $this->invites()->create([
                    'email' => $email,
                    'inviter_id' => auth()->id(),
                    'role_id' => $programAdminRole->id,
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
                // add user to program if user does not belong to this program yet
                if ($this->users->contains($user)) {
                    // show notification
                    Notification::make()
                        ->success()
                        ->title('User already in this program')
                        ->body('User ' . $email . ' belongs to this program already')
                        ->send();
                } else {
                    // add invites model for future tracing
                    $invite = $this->invites()->create([
                        'email' => $email,
                        'inviter_id' => auth()->id(),
                        'role_id' => $programAdminRole->id,
                        'token' => 'na',
                        'is_confirmed' => true,
                    ]);

                    // add user to this program
                    $this->users()->attach($user);

                    // show notification
                    Notification::make()
                        ->success()
                        ->title('User added')
                        ->body('User ' . $email . ' has been added to this program')
                        ->send();

                    // send email notification to inform user that he/she has been added to a program
                    Mail::to($invite->email)->send(new UpdateUser($invite));

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

    public function invites(): HasMany
    {
        return $this->hasMany(Invite::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            related: config('filament-team-management.models.user'),
            table: config('filament-team-management.table_names.program_members'),
            foreignPivotKey: config('filament-team-management.column_names.programs_foreign_key'),
            relatedPivotKey: config('filament-team-management.column_names.users_foreign_key'),
        )->withTimestamps();
    }

    public function members(): BelongsToMany
    {
        return $this->users();
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(
            related: config('filament-team-management.models.team'),
            table: config('filament-team-management.table_names.program_teams'),
            foreignPivotKey: config('filament-team-management.column_names.programs_foreign_key'),
            relatedPivotKey: config('filament-team-management.column_names.teams_foreign_key'),
        )->withTimestamps();
    }

    // add relationship to refer to program model itself, so that program admin panel > Programs resource can show the selected program for editing
    public function program(): HasOne
    {
        return $this->hasOne(Program::class, 'id');
    }
}
