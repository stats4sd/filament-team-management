<?php

use Filament\Facades\Filament;
use Filament\Schemas\Schema;
use Stats4sd\FilamentTeamManagement\Filament\Program\Pages\ManageProgram\ManageProgram;
use Stats4sd\FilamentTeamManagement\Filament\Program\Pages\ManageProgram\ManageProgramMembers;
use Stats4sd\FilamentTeamManagement\Filament\Program\Pages\ManageProgram\ManageProgramTeams;
use Stats4sd\FilamentTeamManagement\Models\Program;

/*
 * Guards bug 2.2 (copy-paste half): ProgramMembersTable interpolated config('...names.team')
 * where it should read names.program. Overriding ONLY names.program (leaving names.team at its
 * default) proves the program members table now consumes the program word, not the team word.
 */
it('renders the configured program word in the members invite callout (guards 2.2 copy-paste)', function () {
    config()->set('filament-team-management.names.program', 'initiative');

    $user = actingAsProgramAdmin();
    $program = Program::factory()->create();
    $program->users()->attach($user);

    Filament::setCurrentPanel(Filament::getPanel('program'));
    Filament::setTenant($program);

    $widget = livewire(ManageProgramMembers::class)->instance();
    $action = $widget->getTable()->getAction('invite');

    expect($action->getModalDescription())->toContain('this initiative');
});

/*
 * A7 (2.5): the ManageProgram tab over the program's teams was hardcoded "Projects". It now
 * follows names.team (pluralised), so a host that calls teams "projects" gets "Projects" and a
 * default install gets "Teams". Overriding only names.team proves the label reads that key.
 */
it('labels the ManageProgram teams tab from the configured team word', function () {
    config()->set('filament-team-management.names.team', 'site');

    $admin = actingAsAdmin();
    $program = Program::factory()->create();
    $program->users()->attach($admin);

    $this->get(ManageProgram::getUrl(tenant: $program, panel: 'program'))
        ->assertOk()
        ->assertSee('Sites')
        ->assertDontSee('Projects');
});

it('labels the ManageProgram page and name field from the configured program word', function () {
    config()->set('filament-team-management.names.program', 'initiative');

    expect(ManageProgram::getLabel())->toBe('Manage Initiative');

    $admin = actingAsAdmin();
    $program = Program::factory()->create();
    $program->users()->attach($admin);

    $this->get(ManageProgram::getUrl(tenant: $program, panel: 'program'))
        ->assertOk()
        ->assertSee('Enter a name for the initiative');
});

it('uses the configured team noun in the existing-team selector', function () {
    config()->set('filament-team-management.names.team', 'site');
    actingAsAdmin();
    $program = Program::factory()->create();
    Filament::setCurrentPanel(Filament::getPanel('program'));
    Filament::setTenant($program);
    $widget = livewire(ManageProgramTeams::class)->instance();
    $action = $widget->getTable()->getAction('attach');
    $field = $action->getSchema(Schema::make($widget))->getComponents()[0];
    expect($field->getLabel())->toBe('Site');
});
