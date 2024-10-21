<?php

namespace Stats4sd\FilamentTeamManagement\Models;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

use Stats4sd\FilamentTeamManagement\Mail\InviteMember;

use Stats4sd\FilamentOdkLink\Services\OdkLinkService;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends \Stats4sd\FilamentOdkLink\Models\TeamManagement\Team
{
    protected $table = 'teams';

    protected $guarded = ['id'];


    // TODO: I think this overrides the booted method on HasXlsForms - ideally we wouldn't need to copy the package stuff here...
    protected static function booted(): void
    {

        // when the model is created; automatically create an associated project on ODK Central and a top location level;
        static::created(static function ($owner) {

            // check if we are in local-only (no-ODK link) mode
            $odkLinkService = app()->make(OdkLinkService::class);
            if (config('filament-odk-link.odk.url') !== null && config('filament-odk-link.odk.url') !== '') {
                $owner->createLinkedOdkProject($odkLinkService, $owner);
            }

            // create empty interpretation entries for the team:
            // TODO: this probably is not great, and we should not require a bunch of empty entries!

            // Below are tape-data-system specific business logic, HOPLA may have something similar.
            // Temporary keep it for reference first. We can remove them after confirming we do not need them.

            /*
            $interpretations = CaetIndex::all()->map(fn ($index) => [
               'owner_id' => $owner->id,
               'owner_type' => static::class,
               'caet_index_id' => $index->id,
               'interpretation' => '',
           ])->toArray();

           $owner->caetInterpretations()->createMany($interpretations);

           $owner->locationLevels()->create(['name' => 'Top level (rename)', 'has_farms' => 0, 'top_level' => 1, 'slug' =>'site-level']);
           */
        });
    }

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
        return $this->hasMany(TeamInvite::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_members')
            ->withPivot('is_admin');
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_members')
            ->withPivot('is_admin')
            ->wherePivot('is_admin', 1);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_members')
            ->withPivot('is_admin')
            ->wherePivot('is_admin', 0);
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class);
    }

    // add relationship to refer to team model itself, so that app panel > Teams resource can show the selected team for editing
    public function team(): HasOne
    {
        return $this->hasOne(Team::class, 'id');
    }
}
