<?php

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Programs\Pages\ViewProgram;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Programs\RelationManagers\InvitesRelationManager as ProgramInvitesRelationManager;
use Stats4sd\FilamentTeamManagement\Filament\App\Pages\ManageTeam\ManageTeamInvites;
use Stats4sd\FilamentTeamManagement\Filament\Program\Pages\ManageProgram\ManageProgramInvites;
use Stats4sd\FilamentTeamManagement\Filament\Program\Pages\ManageProgram\ManageProgramMembers;
use Stats4sd\FilamentTeamManagement\Models\Invite;
use Stats4sd\FilamentTeamManagement\Models\Program;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Models\User;

$programKey = fn () => config('filament-team-management.column_names.programs_foreign_key');
$teamKey = fn () => config('filament-team-management.column_names.teams_foreign_key');

beforeEach(function () {
    $this->admin = actingAsAdmin();
});

// Fix 4.4 + 4.13: the Program Invites tenant table renders the program.name
// column (was the nonexistent project.name) with a working inverseRelationship.
// Component-render level via the ManageProgramInvites TableWidget bound to the
// program tenant. Pre-fix, assertCanRenderTableColumn('program.name') fails
// because the column key is project.name.
it('renders the program.name column on the Program invites table', function () use ($programKey) {
    $program = Program::factory()->create(['name' => 'Alpha Program']);

    Filament::setCurrentPanel(Filament::getPanel('program'));
    Filament::setTenant($program);

    Invite::factory()->create([
        'email' => 'pending@example.test',
        $programKey() => $program->id,
    ]);

    $component = livewire(ManageProgramInvites::class)
        ->assertSuccessful()
        ->assertCanRenderTableColumn('program.name')
        ->assertSee('Alpha Program');

    // Fix 4.13: inverseRelationship was 'teams'; the invite's inverse is 'program'.
    expect($component->instance()->getTable()->getInverseRelationship())->toBe('program');
});

// Fix 4.4 + 4.13: the Team Invites tenant table (program column only visible in
// program mode) renders program.name for an invite carrying a program_id.
it('renders the program.name column on the Team invites table in program mode', function () use ($teamKey, $programKey) {
    $team = Team::factory()->create();
    $program = Program::factory()->create(['name' => 'Bravo Program']);

    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($team);

    Invite::factory()->create([
        'email' => 'pending2@example.test',
        $teamKey() => $team->id,
        $programKey() => $program->id,
    ]);

    $component = livewire(ManageTeamInvites::class)
        ->assertSuccessful()
        ->assertCanRenderTableColumn('program.name')
        ->assertSee('Bravo Program');

    // Fix 4.13: inverseRelationship was 'teams'; the invite's inverse is 'team'.
    expect($component->instance()->getTable()->getInverseRelationship())->toBe('team');
});

// Fix 4.13: the Program members table declares inverseRelationship('programs')
// (was 'teams'); the widget renders and lists the program's members.
it('renders the Program members table with the corrected inverse relationship', function () {
    $program = Program::factory()->create();
    $member = User::factory()->create(['email' => 'member@example.test']);
    $program->members()->attach($member);

    Filament::setCurrentPanel(Filament::getPanel('program'));
    Filament::setTenant($program);

    $component = livewire(ManageProgramMembers::class)
        ->assertSuccessful()
        ->assertSee('member@example.test');

    // Fix 4.13: inverseRelationship was 'teams'; the members table is Program
    // members (Users) whose inverse on User is 'programs'. Rendering alone does
    // not exercise the inverse, so assert it at the table-definition level.
    expect($component->instance()->getTable()->getInverseRelationship())->toBe('programs');
});

// Fix 4.8: the admin Program Invites relation manager exposes no Create action
// but keeps Delete.
it('exposes no Create action but keeps Delete on the Program Invites relation manager', function () use ($programKey) {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $program = Program::factory()->create();
    $invite = Invite::factory()->create([$programKey() => $program->id]);

    livewire(ProgramInvitesRelationManager::class, [
        'ownerRecord' => $program,
        'pageClass' => ViewProgram::class,
    ])
        ->assertActionDoesNotExist(TestAction::make(CreateAction::getDefaultName())->table())
        ->assertActionExists(TestAction::make(DeleteAction::getDefaultName())->table($invite));
});
