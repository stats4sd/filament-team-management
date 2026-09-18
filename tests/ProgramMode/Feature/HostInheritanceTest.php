<?php

use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Stats4sd\FilamentTeamManagement\Actions\AddMember;
use Stats4sd\FilamentTeamManagement\Actions\UnlinkTeamFromProgram;
use Stats4sd\FilamentTeamManagement\Models\Program;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\HostUser;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Policies\MembershipPolicy;

class InheritedAccessHostUser extends HostUser
{
    public function accessible(Panel $panel): ?Builder
    {
        if ($panel->getTenantModel() === config('filament-team-management.models.team') && config('filament-team-management.use_programs')) {
            return config('filament-team-management.models.team')::query()->where(fn (Builder $query) => $query
                ->whereHas('users', fn (Builder $users) => $users->whereKey($this->getKey()))
                ->orWhereHas('programs.users', fn (Builder $users) => $users->whereKey($this->getKey())));
        }

        return parent::accessible($panel);
    }
}

class InheritedAccessTeamPolicy extends MembershipPolicy
{
    public function view(HostUser $actor, Model $target): bool
    {
        return $actor instanceof InheritedAccessHostUser
            ? $actor->accessible(Filament::getPanel('app'))->whereKey($target->getKey())->exists()
            : parent::view($actor, $target);
    }
}

it('keeps explicit inherited access and management scoped when a team belongs to two programs', function () {
    Gate::policy(Team::class, InheritedAccessTeamPolicy::class);
    $actor = InheritedAccessHostUser::create(['name' => 'Program member', 'email' => 'inherited@example.test', 'password' => 'long-password']);
    $member = HostUser::factory()->create();
    $first = Program::factory()->create();
    $second = Program::factory()->create();
    $shared = Team::factory()->create();
    $unrelated = Team::factory()->create();
    $first->users()->attach($actor);
    $first->teams()->attach($shared);
    $second->teams()->attach([$shared->getKey(), $unrelated->getKey()]);
    $actor->grant($first);
    $this->actingAs($actor);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    expect($actor->getTenants(Filament::getPanel('app'))->modelKeys())->toBe([$shared->getKey()])
        ->and($actor->canAccessTenant($shared))->toBeTrue()
        ->and(Gate::forUser($actor)->allows('view', $shared))->toBeTrue()
        ->and($actor->canAccessTenant($unrelated))->toBeFalse()
        ->and(Gate::forUser($actor)->allows('view', $second))->toBeFalse()
        ->and($actor->teams()->count())->toBe(0);
    expect(fn () => app(AddMember::class)->handle($actor, $shared, $member))->toThrow(AuthorizationException::class);
    $actor->grant($shared, 'addMember');
    expect(app(AddMember::class)->handle($actor, $shared, $member))->toBeTrue();
    $actor->grant($shared, 'unlinkProgram');
    app(UnlinkTeamFromProgram::class)->handle($actor, $first, $shared);
    expect($second->teams()->whereKey($shared->getKey())->exists())->toBeTrue()
        ->and($actor->getTenants(Filament::getPanel('app')))->toBeEmpty()
        ->and($actor->canAccessTenant($shared))->toBeFalse()
        ->and(Gate::forUser($actor)->allows('view', $shared))->toBeFalse();
});
