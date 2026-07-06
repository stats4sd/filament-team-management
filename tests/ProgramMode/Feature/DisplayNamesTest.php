<?php

use Filament\Facades\Filament;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Schema;
use Stats4sd\FilamentTeamManagement\Filament\Program\Pages\ManageProgram\ManageProgramMembers;
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
    $action = $widget->getTable()->getAction('Invite');

    $callout = collect($action->getForm(Schema::make($widget))->getComponents())
        ->first(fn ($c) => $c instanceof Callout);

    expect($callout)->not->toBeNull()
        ->and($callout->getDescription())->toContain('invite to this initiative');
});
