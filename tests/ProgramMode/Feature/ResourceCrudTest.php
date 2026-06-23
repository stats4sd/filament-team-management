<?php

use Filament\Actions\DeleteBulkAction;
use Filament\Facades\Filament;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Programs\Pages\ListPrograms;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Users\Pages\ListUsers;
use Stats4sd\FilamentTeamManagement\Models\Program;
use Stats4sd\FilamentTeamManagement\Models\User;

beforeEach(function () {
    actingAsAdmin();
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('lists programs in the Admin Programs resource', function () {
    $programs = Program::factory()->count(2)->create();

    livewire(ListPrograms::class)->assertCanSeeTableRecords($programs);
});

it('bulk-deletes users (programs relation eager-loads cleanly in program mode)', function () {
    $users = User::factory()->count(2)->create();

    livewire(ListUsers::class)
        ->callTableBulkAction(DeleteBulkAction::getDefaultName(), $users);

    expect(User::whereIn('id', $users->pluck('id'))->count())->toBe(0);
});
