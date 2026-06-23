<?php

use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Teams\TeamResource;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Users\UserResource;

beforeEach(function () {
    actingAsAdmin();
});

it('renders the Admin Users list page', function () {
    $this->get(UserResource::getUrl('index', panel: 'admin'))->assertOk();
});

it('renders the Admin Teams list page', function () {
    $this->get(TeamResource::getUrl('index', panel: 'admin'))->assertOk();
});
