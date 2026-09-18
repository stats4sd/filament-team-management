<?php

namespace Stats4sd\FilamentTeamManagement\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\Livewire\Partials\DataStoreOverride;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Livewire\LivewireServiceProvider;
use Livewire\Mechanisms\DataStore;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;
use Stats4sd\FilamentTeamManagement\FilamentTeamManagementServiceProvider;
use Stats4sd\FilamentTeamManagement\Models\User;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\HostUser;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Policies\MembershipPolicy;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Policies\UserPolicy;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\TestPanelProvider;

class TestCase extends Orchestra
{
    /**
     * When true, the harness runs the program migrations and registers the
     * Program panel. Program-mode test cases flip this (see withPrograms()).
     */
    protected bool $usePrograms = false;

    protected array $panelIds = ['app' => 'app', 'program' => 'program', 'admin' => 'admin'];

    protected array $panelPaths = ['app' => 'app', 'program' => 'program', 'admin' => 'admin'];

    protected ?string $tenantSlugAttribute = null;

    protected bool $registration = false;

    protected function setUp(): void
    {
        // Must be set before parent::setUp() creates the application, so the
        // test panel provider can read it when it registers (which happens
        // before Testbench applies our environment config).
        TestPanelProvider::$usePrograms = $this->usePrograms;
        TestPanelProvider::$panelIds = $this->panelIds;
        TestPanelProvider::$panelPaths = $this->panelPaths;
        TestPanelProvider::$tenantSlugAttribute = $this->tenantSlugAttribute;
        TestPanelProvider::$registration = $this->registration;

        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Stats4sd\\FilamentTeamManagement\\Database\\Factories\\' . (is_a($modelName, User::class, true) ? 'User' : class_basename($modelName)) . 'Factory'
        );

        Gate::policy(config('filament-team-management.models.team'), MembershipPolicy::class);
        Gate::policy(config('filament-team-management.models.program'), MembershipPolicy::class);
        Gate::policy(config('filament-team-management.models.user'), UserPolicy::class);
    }

    protected function getPackageProviders($app)
    {
        return [
            ActionsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            LivewireServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentTeamManagementServiceProvider::class,
            TestPanelProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        config()->set('app.key', 'base64:' . base64_encode(random_bytes(32)));

        // The auth provider uses our explicit host integration model.
        config()->set('filament-team-management.models.user', HostUser::class);
        config()->set('auth.defaults.guard', 'web');
        config()->set('auth.guards.web', ['driver' => 'session', 'provider' => 'users']);
        config()->set('auth.providers.users.model', config('filament-team-management.models.user'));

        config()->set('filament-team-management.use_programs', $this->usePrograms);
        config()->set('filament-team-management.panels', $this->panelIds);

        // Filament's SupportServiceProvider binds Livewire's DataStore with a
        // non-shared bind() (to DataStoreOverride). In a real app Livewire's
        // shared instance() binding wins by provider order; in this manually
        // ordered harness Filament's non-shared bind wins, so every
        // app(DataStore) call returns a fresh store and Livewire's per-component
        // state (e.g. the validation error bag) never persists — every render
        // then trips over a null error bag. Re-bind it as a shared singleton.
        // This runs after all providers register, so it wins.
        $app->singleton(DataStore::class, DataStoreOverride::class);
    }

    /**
     * Run the package migrations against the in-memory connection.
     *
     * The package ships `.stub` migrations (so Testbench won't auto-discover
     * them), so we include and run
     * each migration by hand in dependency order. Laravel's default migrations
     * (users, cache, jobs, …) are loaded first for the base `users` table.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadLaravelMigrations();

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('host_admin')->default(false);
        });
        Schema::create('host_grants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('target_type');
            $table->unsignedBigInteger('target_id');
            $table->string('ability');
            $table->unique(['user_id', 'target_type', 'target_id', 'ability']);
        });

        // Package default migrations. These always create the (unconstrained)
        // program columns on invites and users, so they never depend on
        // use_programs and can run before the program set.
        foreach (['1_create_teams_table', '2_create_team_members_table', '3_create_invites_table', '9_add_column_to_users_table'] as $migration) {
            $this->runStubMigration($this->migrationPath($migration));
        }

        // Program migrations (tables + the foreign keys on the program columns),
        // when this test case opts into programs.
        if ($this->usePrograms) {
            $this->runProgramMigrations();
        }

    }

    /**
     * Run the program migration set in order: the three program tables, then
     * the foreign-key constraints on invites.program_id / users.latest_program_id
     * (which the default set already created as plain nullable columns).
     */
    protected function runProgramMigrations(): void
    {
        foreach (['5_create_programs_table', '6_create_program_members_table', '7_create_program_team_table', '10_add_program_foreign_keys'] as $migration) {
            $this->runStubMigration($this->migrationPath($migration));
        }
    }

    protected function runStubMigration(string $path): void
    {
        $migration = include $path;
        $migration->up();
    }

    protected function migrationPath(string $name): string
    {
        return dirname(__DIR__) . "/database/migrations/{$name}.php.stub";
    }

    protected function vendorPath(string $path): string
    {
        return dirname(__DIR__) . '/vendor/' . ltrim($path, '/');
    }

    /**
     * Flip the harness into program mode at runtime: enable the config flag and
     * run the full program migration set (tables + foreign keys; the program
     * columns themselves already exist from the default set). Call at the top
     * of a test that needs the program tables present.
     *
     * Note: the Program panel is registered at boot, so tests that need it
     * still require a dedicated program-mode test case (ProgramTestCase).
     */
    public function withPrograms(): static
    {
        config()->set('filament-team-management.use_programs', true);

        $this->runProgramMigrations();

        return $this;
    }

    /**
     * Create a host administrator and act as them.
     */
    public function actingAsAdmin(): User
    {
        /** @var User $user */
        $user = config('filament-team-management.models.user')::factory()->create();
        $user->forceFill(['host_admin' => true])->save();

        $this->actingAs($user);

        return $user;
    }

    /**
     * Create a host administrator and act as them.
     */
    public function actingAsProgramAdmin(): User
    {
        /** @var User $user */
        $user = config('filament-team-management.models.user')::factory()->create();
        $user->forceFill(['host_admin' => true])->save();

        $this->actingAs($user);

        return $user;
    }
}
