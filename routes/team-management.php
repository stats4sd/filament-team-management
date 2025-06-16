<?php

use Illuminate\Support\Facades\Route;
use Stats4sd\FilamentTeamManagement\Filament\App\Pages\RegisterNewUser;

Route::group([
    'middleware' => ['web'],
], function () {

    // user registration form for team-invites
    Route::get('register', RegisterNewUser::class)
        ->name('filament.app.register-invite')
        ->middleware('signed');
});
