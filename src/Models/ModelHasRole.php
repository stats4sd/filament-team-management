<?php

namespace Stats4sd\FilamentTeamManagement\Models;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
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
            // find email address
            $user = User::find($item->model_id);
            $email = $user->email;

            $role = Role::find($item->role_id);

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

                // save invite model
                $invite->save();

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
