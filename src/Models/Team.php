<?php

namespace Stats4sd\FilamentTeamManagement\Models;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Stats4sd\FilamentTeamManagement\Mail\InviteUser;
use Stats4sd\FilamentTeamManagement\Models\Interfaces\TeamInterface;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 */
class Team extends Model implements TeamInterface
{
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
                ->body('An email invitation has been successfully sent to '.$email)
                ->send();
        }
    }

    /** @return HasMany<Invite, $this> */
    public function invites(): HasMany
    {
        return $this->hasMany(Invite::class, foreignKey: 'team_id', localKey: 'id');
    }

    /** @return BelongsToMany<Model, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(config('filament-team-management.models.user'),
            static::getModelNameLower().'_members',
            static::getModelNameLower().'_id',
            'user_id')
            ->withPivot('is_admin');
    }

    /** @return BelongsToMany<Model, $this> */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(
            config('filament-team-management.models.user'),
            static::getModelNameLower().'_members',
            static::getModelNameLower().'_id',
            'user_id')
            ->withPivot('is_admin')
            ->wherePivot('is_admin', 1);
    }

    /** @return BelongsToMany<Model, $this> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            config('filament-team-management.models.user'),
            static::getModelNameLower().'_members',
            static::getModelNameLower().'_id',
            'user_id')
            ->withPivot('is_admin')
            ->wherePivot('is_admin', 0);
    }

    /** @return BelongsToMany<Model, $this> */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(
            config('filament-team-management.models.program'),
            'program_'.static::getModelNameLower(),
            static::getModelNameLower().'_id',
            'program_id');
    }

    // add relationship to refer to team model itself, so that app panel > Teams resource can show the selected team for editing
    /** @return HasOne<self, $this> */
    public function team(): HasOne
    {
        return $this->hasOne(Team::class, 'id');
    }

    public static function getModelNameLower(): string
    {
        $teamClass = config('filament-team-management.models.team') ?? self::class;

        return Str::of($teamClass)->afterLast('\\')->lower();
    }
}
