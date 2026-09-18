<?php

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Programs\Pages\ViewProgram;
use Stats4sd\FilamentTeamManagement\Filament\Admin\Resources\Programs\RelationManagers\UsersRelationManager;
use Stats4sd\FilamentTeamManagement\Filament\Program\Pages\ManageProgram\ManageProgramMembers;
use Stats4sd\FilamentTeamManagement\Models\Program;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\ContextualCandidatePicker;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\HostUser as User;

beforeEach(function () {
    $this->actor = actingAsAdmin();
    $this->program = Program::factory()->create();
    $this->eligible = User::factory()->create(['email' => 'program@eligible.test']);
    $this->excluded = User::factory()->create(['email' => 'excluded@eligible.test']);
    $this->picker = new ContextualCandidatePicker;
    $this->picker->allow($this->actor, $this->program, [$this->eligible->getKey()]);
    app()->instance(ContextualCandidatePicker::class, $this->picker);
    config()->set('filament-team-management.user_picker', ContextualCandidatePicker::class);
});

it('adds eligible program members through both packaged surfaces', function (string $surface) {
    if ($surface === 'widget') {
        Filament::setCurrentPanel(Filament::getPanel('program'));
        Filament::setTenant($this->program);
        $component = livewire(ManageProgramMembers::class);
    } else {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $component = livewire(UsersRelationManager::class, ['ownerRecord' => $this->program, 'pageClass' => ViewProgram::class]);
    }
    $component->callAction(TestAction::make('attach')->table(), ['recordId' => [$this->eligible->getKey()]])
        ->assertHasNoActionErrors();
    expect($this->program->users()->get()->modelKeys())->toBe([$this->eligible->getKey()]);
})->with(['widget', 'relation manager']);

it('rejects a mixed forged program selection through the mounted shared attachment action', function () {
    Filament::setCurrentPanel(Filament::getPanel('program'));
    Filament::setTenant($this->program);
    livewire(ManageProgramMembers::class)
        ->assertActionVisible(TestAction::make('attach')->table())
        ->mountAction(TestAction::make('attach')->table())
        ->assertActionMounted(TestAction::make('attach')->table())
        ->setActionData(['recordId' => [$this->eligible->getKey(), $this->excluded->getKey()]])
        ->callMountedAction()->assertForbidden();
    expect($this->program->users()->count())->toBe(0);
});
