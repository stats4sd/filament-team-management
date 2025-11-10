<?php

use Illuminate\Support\Facades\Route;
use Stats4sd\FilamentTeamManagement\Filament\Auth\Register;

Route::group([
    'middleware' => ['web'],
], function () {

//    // user registration form for team-invites
//    Route::get('register', RegisterNewUser::class)
//        ->name('filament.app.register-invite')
//        ->middleware('signed');
});
