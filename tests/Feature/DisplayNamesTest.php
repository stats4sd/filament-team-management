<?php

use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Schema;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Programs\Pages\ViewProgram;
use Stats4sd\FilamentTeamManagement\Filament\App\Pages\ManageTeam\ManageTeamMembers;
use Stats4sd\FilamentTeamManagement\Models\Team;

/*
 * Guards bug 2.2: the UI copy interpolated config('...names.team') / names.program, keys
 * that did not exist, so users saw a literal blank gap ("…invite to this ."). With the
 * names block present, the configured word must flow through the actual label-building code.
 * These assert the shipped defaults render (no blank); ConfigIndirectionTest proves overrides flow.
 */

it('renders the team word in the members invite callout, not a blank gap (guards 2.2)', function () {
    $user = actingAsAdmin();
    $team = Team::factory()->create();
    $user->teams()->attach($team);

    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($team);

    $widget = livewire(ManageTeamMembers::class)->instance();
    $action = $widget->getTable()->getAction('Invite');

    $callout = collect($action->getForm(Schema::make($widget))->getComponents())
        ->first(fn ($c) => $c instanceof Callout);

    expect($callout)->not->toBeNull()
        ->and($callout->getDescription())->toContain('invite to this team');
});

it('renders the program word in the delete confirmation, not a blank gap (guards 2.2)', function () {
    $page = new ViewProgram;

    $method = new ReflectionMethod($page, 'getHeaderActions');
    $method->setAccessible(true);

    $delete = collect($method->invoke($page))
        ->first(fn ($a) => $a instanceof DeleteAction);

    expect($delete)->not->toBeNull()
        ->and($delete->getModalDescription())->toContain('permanently delete this program');
});
