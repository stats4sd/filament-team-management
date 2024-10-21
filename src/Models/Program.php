<?php

namespace Stats4sd\FilamentTeamManagement\Models;

use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

use Stats4sd\FilamentTeamManagement\Mail\InviteProgramAdmin;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Program extends Model
{
    protected $table = 'programs';

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

            $invite = $this->invites()->create([
                'email' => $email,
                'inviter_id' => auth()->id(),
                'role_id' => $programAdminRole->id,
                'token' => Str::random(24),
            ]);

            Mail::to($invite->email)->send(new InviteProgramAdmin($invite));

            // show notification after sending invitation email to user
            Notification::make()
                ->success()
                ->title('Invitation Sent')
                ->body('An email invitation has been successfully sent to ' . $email)
                ->send();
        }
    }


    public function invites(): HasMany
    {
        return $this->hasMany(ProgramInvite::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)->withTimestamps();
    }

    // add relationship to refer to program model itself, so that program admin panel > Programs resource can show the selected program for editing
    public function program(): HasOne
    {
        return $this->hasOne(Program::class, 'id');
    }
}
