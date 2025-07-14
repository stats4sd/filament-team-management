<?php

namespace Stats4sd\FilamentTeamManagement\Models;

use Illuminate\Database\Eloquent\Model;

class ModelHasRole extends Model
{
    protected $table = 'model_has_roles';

    protected $guarded = ['id'];

    // ****** TEAM MANAGEMENT STUFF ******

    public static function boot()
    {
        parent::boot();

        logger('ModelHasRole.boot()...');

        // ModelHasRole model event "created" is not triggered when a new model_has_roles record is created.
        static::created(function ($item) {
            logger('ModelHasRole.created()...');

            // TODO: check if there is invites record for this role
            // if invites record found, email invite should have been sent before, no need to send email notification
            // if no invites record found, send email notification to user (this role should be added by Super Admin in admin panel > Users resource > edit page)

            logger('role_id: ' . $item->role_id);
            logger('model_id: ' . $item->model_id);

        });

    }

}
