<?php

namespace Stats4sd\FilamentTeamManagement\Models;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Stats4sd\FilamentTeamManagement\Mail\AddRoleToUser;

class ModelHasRole extends Pivot
{
    protected $table = 'model_has_roles';

    protected $guarded = ['id'];

    // ****** TEAM MANAGEMENT STUFF ******

    public static function boot()
    {
        parent::boot();

        // before creating model_has_roles record
        static::creating(function ($item) {
            // assumes User model is the only model to have role in this application, fill in default value to column "model_type"
            $item->model_type = 'App\Models\User';
        });

        // after creating model_has_roles record
        static::created(function ($item) {
            // find email address
            $user = User::find($item->model_id);
            $email = $user->email;

            $role = Role::find($item->role_id);


            // Question: 
            //
            // Should we send email notificaiton to user when a new role is attached, regardless if there is email invite sent to user before?
            //
            // When we invite an unregistered user in admin panel > Users resource > Invite users button, it allows to select one role only.
            // It should be fine to receive an email after user registration, to indicate which role has been attached to user.
            //
            // From time to time, super admin can attach and/or detach role of a user.
            //
            // If a user has invites record for role "Program Admin", after some time this role is detached, and then this role is attached again some time later.
            // As user has invites record for this role at the very beginning, no email notification will be sent when this role attached to user again.
            //
            // I think it should be fine to send email notification to user when a role is attached. This email just reflects what happened.

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
            Mail::to($invite->email)->send(new AddRoleToUser($invite));

            // show notification after sending email notification to user
            Notification::make()
                ->success()
                ->title('Email Notification Sent')
                ->body('An email notification has been successfully sent to ' . $email)
                ->send();
        });

    }
}
