<?php

namespace Stats4sd\FilamentTeamManagement\Models;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Stats4sd\FilamentTeamManagement\Mail\InviteMember;
use Stats4sd\FilamentTeamManagement\Models\Interfaces\TeamInterface;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 */
class Team extends Model implements TeamInterface
{
    public function getTable()
    {
        return static::getModelNameLower() . 's';
    }

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

            $invite = $this->invites()->create([
                'email' => $email,
                'inviter_id' => auth()->id(),
                'token' => Str::random(24),
            ]);

            Mail::to($invite->email)->send(new InviteMember($invite));

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
        return $this->hasMany(TeamInvite::class, foreignKey: static::getModelNameLower() . '_id', localKey: 'id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(config('filament-team-management.models.user'), static::getModelNameLower() . '_members', static::getModelNameLower() . '_id', 'user_id')
            ->withPivot('is_admin');
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(config('filament-team-management.models.user'), static::getModelNameLower() . '_members', static::getModelNameLower() . '_id', 'user_id')
            ->withPivot('is_admin')
            ->wherePivot('is_admin', 1);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(config('filament-team-management.models.user'), static::getModelNameLower() . '_members', static::getModelNameLower() . '_id', 'user_id')
            ->withPivot('is_admin')
            ->wherePivot('is_admin', 0);
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(
            config('filament-team-management.models.program'),
            'program_' . static::getModelNameLower(),
            config('filament-team-management.names.team)' . '_id', 'program_id')
        );
    }

    protected static function getModelNameLower(): string
    {
        return config('filament-team-management.names.team') ?? 'team';
    }

    // add relationship to refer to team model itself, so that app panel > Teams resource can show the selected team for editing
    public function team(): HasOne
    {
        return $this->hasOne(Team::class, 'id');
    }
}
