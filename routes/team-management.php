<?php

use Illuminate\Support\Facades\Route;
use Stats4sd\FilamentTeamManagement\Filament\App\Pages\Programregister;
use Stats4sd\FilamentTeamManagement\Filament\App\Pages\Register;
use Stats4sd\FilamentTeamManagement\Filament\App\Pages\Roleregister;

Route::group([
    'middleware' => ['web'],
], function () {

    // user registration form for team-invites
    Route::get('register', Register::class)
        ->name('filament.app.register')
        ->middleware('signed');

    // user registration form for role-invites
    Route::get('roleregister', Roleregister::class)
        ->name('filament.app.roleregister')
        ->middleware('signed');

    // user registration form for program-invites
    Route::get('programregister', Programregister::class)
        ->name('filament.app.programregister')
        ->middleware('signed');
});
