<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Stats4sd\FilamentTeamManagement\Actions\CreateTeamForProgram;
use Stats4sd\FilamentTeamManagement\Contracts\MembershipParticipant;
use Stats4sd\FilamentTeamManagement\Events\MemberAdded;
use Stats4sd\FilamentTeamManagement\Events\TeamLinkedToProgram;
use Stats4sd\FilamentTeamManagement\Models\Program;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Support\MembershipContext;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\HostUser;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Policies\MembershipPolicy;

class CompositeTeamPolicy extends MembershipPolicy
{
    public function create(HostUser $actor): bool
    {
        return true;
    }
}

class CompositeProgramPolicy extends MembershipPolicy
{
    public function linkTeam(HostUser $actor, Model $target, Model $team): bool
    {
        return true;
    }
}

class CompositeBootstrap implements MembershipParticipant
{
    public array $contexts = [];

    public function before(MembershipContext $context): void
    {
        $this->contexts[] = ['before', $context->payload()];
    }

    public function after(MembershipContext $context): void
    {
        $this->contexts[] = ['after', $context->payload()];
        $context->actor->grant($context->target, $context->operation === 'create_team' ? 'linkProgram' : 'host_audit');
    }
}

class CompositeSoftDeletedProgram extends Program
{
    use SoftDeletes;
}

class CompositeVetoPivot extends Pivot
{
    protected static function booted(): void
    {
        static::creating(fn () => false);
    }
}

class CompositeVetoProgram extends Program
{
    public function teams(): BelongsToMany
    {
        return parent::teams()->using(CompositeVetoPivot::class);
    }
}

beforeEach(function () {
    withPrograms();
    $this->actor = HostUser::factory()->create();
    $this->program = Program::factory()->create();
    $this->bootstrap = new CompositeBootstrap;
    config()->set('filament-team-management.participants', [$this->bootstrap]);
    Gate::policy(Team::class, CompositeTeamPolicy::class);
    Gate::policy(Program::class, CompositeProgramPolicy::class);
    $this->observations = [];
    foreach ([MemberAdded::class, TeamLinkedToProgram::class] as $event) {
        Event::listen($event, function ($event) {
            $this->observations[] = $event;
        });
    }
});

function assertCompositeRolledBack(): void
{
    expect(Team::count())->toBe(0)
        ->and(DB::table('team_members')->count())->toBe(0)
        ->and(DB::table('program_team')->count())->toBe(0)
        ->and(DB::table('host_grants')->count())->toBe(0)
        ->and(test()->observations)->toBe([]);
}

it('creates and links with host bootstrap authority and preserves participant and observation contexts', function () {
    Gate::policy(Program::class, MembershipPolicy::class);
    $this->actor->grant($this->program, 'linkTeam');
    $team = app(CreateTeamForProgram::class)->handle($this->actor, $this->program, ['name' => 'Created together']);

    expect($team)->toBeInstanceOf(Team::class)
        ->and($team->fresh()->name)->toBe('Created together')
        ->and($team->users()->whereKey($this->actor->id)->exists())->toBeTrue()
        ->and($this->program->teams()->whereKey($team->id)->exists())->toBeTrue()
        ->and($this->program->users()->count())->toBe(0)
        ->and($this->actor->allowed($team, 'linkProgram'))->toBeTrue()
        ->and(DB::table('host_grants')->count())->toBe(3);
    expect(array_map(fn ($entry) => [$entry[0], $entry[1]['operation'], $entry[1]['origin'], $entry[1]['changed']], $this->bootstrap->contexts))
        ->toBe([
            ['before', 'create_team', 'bootstrap', false], ['after', 'create_team', 'bootstrap', true],
            ['before', 'link_team', 'direct', false], ['after', 'link_team', 'direct', true],
        ]);
    $creation = $this->bootstrap->contexts[1][1];
    $link = $this->bootstrap->contexts[3][1];
    expect($creation['actor_id'])->toBe($this->actor->id)->and($creation['user_id'])->toBe($this->actor->id)
        ->and($creation['target_id'])->toBe($team->id)->and($creation['target_type'])->toBe(Team::class)
        ->and($link['actor_id'])->toBe($this->actor->id)->and($link['target_id'])->toBe($this->program->id)
        ->and($link['target_type'])->toBe(Program::class)->and($link['team_id'])->toBe($team->id);
    expect($this->observations)->toHaveCount(2)
        ->and($this->observations[0])->toBeInstanceOf(MemberAdded::class)
        ->and($this->observations[0]->payload)->toBe($creation)
        ->and($this->observations[1])->toBeInstanceOf(TeamLinkedToProgram::class)
        ->and($this->observations[1]->payload)->toBe($link);
});

it('fails closed before bootstrap when creation is denied or its policy is absent', function (bool $missing) {
    if ($missing) {
        Gate::swap(new Illuminate\Auth\Access\Gate(app(), fn () => $this->actor, policies: [Program::class => CompositeProgramPolicy::class]));
        expect(Gate::getPolicyFor(Team::class))->toBeNull();
    } else {
        Gate::policy(Team::class, MembershipPolicy::class);
    }
    expect(fn () => app(CreateTeamForProgram::class)->handle($this->actor, $this->program, ['name' => 'Denied']))
        ->toThrow(AuthorizationException::class);
    expect($this->bootstrap->contexts)->toBe([]);
    assertCompositeRolledBack();
})->with([false, true]);

it('rolls back creation and bootstrap when either independent link policy denies', function (string $ability) {
    Gate::before(fn ($actor, $checked) => $checked === $ability ? false : null);
    expect(fn () => app(CreateTeamForProgram::class)->handle($this->actor, $this->program, ['name' => 'Denied link']))
        ->toThrow(AuthorizationException::class);
    expect(array_column(array_column($this->bootstrap->contexts, 1), 'operation'))->toBe(['create_team', 'create_team']);
    expect($this->bootstrap->contexts[1][1]['changed'])->toBeTrue();
    assertCompositeRolledBack();
})->with(['linkTeam', 'linkProgram']);

it('rolls back all writes when a later link participant throws after host grants and the pivot exist', function () {
    $failure = new class implements MembershipParticipant
    {
        public function before(MembershipContext $context): void {}

        public function after(MembershipContext $context): void
        {
            if ($context->operation === 'link_team') {
                expect(DB::table('host_grants')->count())->toBe(2)
                    ->and(DB::table('program_team')->count())->toBe(1);

                throw new RuntimeException('Host link integration failed');
            }
        }
    };
    config()->set('filament-team-management.participants', [$this->bootstrap, $failure]);
    expect(fn () => app(CreateTeamForProgram::class)->handle($this->actor, $this->program, ['name' => 'Failed participant']))
        ->toThrow(RuntimeException::class, 'Host link integration failed');
    assertCompositeRolledBack();
});

it('rolls back the composite when a link pivot vetoes creation', function () {
    config()->set('filament-team-management.models.program', CompositeVetoProgram::class);
    Gate::policy(CompositeVetoProgram::class, CompositeProgramPolicy::class);
    $program = CompositeVetoProgram::findOrFail($this->program->id);
    expect(fn () => app(CreateTeamForProgram::class)->handle($this->actor, $program, ['name' => 'Vetoed link']))
        ->toThrow(ValidationException::class);
    expect($this->bootstrap->contexts)->toHaveCount(3);
    assertCompositeRolledBack();
});

it('defers both observations until the real caller transaction commits and discards them on rollback', function (bool $commit) {
    expect(DB::transactionLevel())->toBe(0);
    DB::beginTransaction();
    $team = app(CreateTeamForProgram::class)->handle($this->actor, $this->program, ['name' => 'Outer transaction']);
    expect(DB::transactionLevel())->toBe(1)->and($this->observations)->toBe([])
        ->and(DB::table('host_grants')->count())->toBe(2)->and($this->program->teams()->whereKey($team->id)->exists())->toBeTrue();
    if ($commit) {
        DB::commit();
        expect($this->observations)->toHaveCount(2)
            ->and($this->observations[0])->toBeInstanceOf(MemberAdded::class)
            ->and($this->observations[1])->toBeInstanceOf(TeamLinkedToProgram::class)
            ->and($team->fresh())->not->toBeNull()->and(DB::table('team_members')->count())->toBe(1)
            ->and(DB::table('program_team')->count())->toBe(1)->and(DB::table('host_grants')->count())->toBe(2);
    } else {
        DB::rollBack();
        assertCompositeRolledBack();
        DB::transaction(fn () => null);
        expect($this->observations)->toBe([]);
    }
})->with([true, false]);

it('rejects invalid context before creating anything', function (string $scenario) {
    $program = $this->program;
    $actor = $this->actor;
    if ($scenario === 'disabled') {
        config()->set('filament-team-management.use_programs', false);
    } elseif ($scenario === 'wrong type') {
        $program = new Team;
    } elseif ($scenario === 'unpersisted') {
        $program = new Program;
    } elseif ($scenario === 'actor') {
        $actor = new HostUser;
    } else {
        config()->set('database.connections.other', config('database.connections.testing'));
        $program = (clone $program)->setConnection('other');
    }
    expect(fn () => app(CreateTeamForProgram::class)->handle($actor, $program, ['name' => 'Invalid']))
        ->toThrow(ValidationException::class);
    expect($this->bootstrap->contexts)->toBe([]);
    assertCompositeRolledBack();
})->with(['disabled', 'wrong type', 'unpersisted', 'actor', 'connection']);

it('rolls back bootstrap when the locked program no longer exists or is ineligible', function (bool $softDeleted) {
    $program = $this->program;
    if ($softDeleted) {
        Schema::table('programs', fn ($table) => $table->softDeletes());
        config()->set('filament-team-management.models.program', CompositeSoftDeletedProgram::class);
        $program = CompositeSoftDeletedProgram::findOrFail($program->id);
        $program->delete();
    } else {
        DB::table('programs')->where('id', $program->id)->delete();
    }
    expect(fn () => app(CreateTeamForProgram::class)->handle($this->actor, $program, ['name' => 'Gone program']))
        ->toThrow(ModelNotFoundException::class);
    expect($this->bootstrap->contexts)->toHaveCount(2);
    assertCompositeRolledBack();
})->with([false, true]);
