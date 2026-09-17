<?php

namespace Stats4sd\FilamentTeamManagement\Models;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Support\Facades\Mail;
use Stats4sd\FilamentTeamManagement\Mail\UpdateUser;

class ModelHasRole extends MorphPivot
{
    public function getTable()
    {
        return config('permission.table_names.model_has_roles') ?? 'model_has_roles';
    }

    protected $guarded = ['id'];

    // ****** TEAM MANAGEMENT STUFF ******

    public static function boot()
    {
        parent::boot();

        // after creating model_has_roles record
        static::created(function ($item) {
            // Phase 2: replace this pivot-event heuristic with an explicit UserRoleAssigned event + listener.
            // Only act on roles assigned to the configured user model; rows for any other morph target
            // (e.g. a role attached to a non-user model) are not ours to trace and would look up the wrong table.
            // Compare against the model's morph class so a host-registered morph-map alias still matches.
            $userClass = config('filament-team-management.models.user');
            if ($item->model_type !== (new $userClass)->getMorphClass()) {
                return;
            }

            // find email address
            $user = $userClass::find($item->model_id);
            $email = $user->email;

            $role = config('filament-team-management.models.role')::find($item->role_id);

            // it is new user registration if auth()->id() is null, otherwise it is Super Admin adding role to an existing user
            if (auth()->id() != null) {
                // create invite model for future tracing
                $invite = Invite::create([
                    'email' => $email,
                    'inviter_id' => auth()->id(),
                    'role_id' => $item->role_id,
                    'token' => 'na',
                    'is_confirmed' => true,
                ]);

                // show notification
                Notification::make()
                    ->success()
                    ->title('Role Assigned to user')
                    ->body('User ' . $email . ' has been assigned role ' . $role->name)
                    ->send();

                // send email notification to inform user that he/she has been assigned a role
                Mail::to($invite->email)->send(new UpdateUser($invite));

                // show notification after sending email notification to user
                Notification::make()
                    ->success()
                    ->title('Email Notification Sent')
                    ->body('An email notification has been successfully sent to ' . $email)
                    ->send();
            }
        });

    }
}
