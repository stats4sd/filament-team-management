<?php

namespace Stats4sd\FilamentTeamManagement\Tests\Fixtures;

use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Stats4sd\FilamentTeamManagement\Filament\App\Pages\ManageTeam\ManageTeam;
use Stats4sd\FilamentTeamManagement\Filament\Auth\Login;
use Stats4sd\FilamentTeamManagement\Filament\Auth\Register;
use Stats4sd\FilamentTeamManagement\Filament\Program\Pages\ManageProgram\ManageProgram;
use Stats4sd\FilamentTeamManagement\Http\Middleware\AuthenticateThroughDefaultPanel;
use Stats4sd\FilamentTeamManagement\Http\Middleware\SetLatestProgramMiddleware;
use Stats4sd\FilamentTeamManagement\Http\Middleware\SetLatestTeamMiddleware;

/**
 * Throwaway panel provider used only by the test harness.
 *
 * The package ships no panel of its own, but most of its behaviour (tenancy,
 * resources, tenant-profile pages) only exists once mounted in a panel. This
 * provider registers the three panels a host app is expected to wire up — App
 * (Team tenant), Admin (no tenant), and Program (Program tenant) — discovering
 * the package's resources/pages so resource/page/tenant tests have somewhere
 * to mount into.
 */
class TestPanelProvider extends PanelProvider
{
    /**
     * Whether to register the Program panel. Set by the test case before the
     * application is created (and thus before register() runs), because
     * Testbench applies the environment config only after providers register —
     * too late to read config('filament-team-management.use_programs') here.
     */
    public static bool $usePrograms = false;

    protected function packagePath(string $path): string
    {
        return dirname(__DIR__, 2) . '/' . ltrim($path, '/');
    }

    public function panel(Panel $panel): Panel
    {
        return $this->appPanel($panel);
    }

    /**
     * A package host app wires up several panels; the base PanelProvider only
     * registers one, so register the rest here. The Program panel is gated on
     * the same flag the host app would use (read from the static, see above).
     */
    public function register(): void
    {
        Filament::registerPanel(fn (): Panel => $this->appPanel(Panel::make()));
        Filament::registerPanel(fn (): Panel => $this->adminPanel(Panel::make()));

        if (static::$usePrograms) {
            Filament::registerPanel(fn (): Panel => $this->programPanel(Panel::make()));
        }
    }

    protected function appPanel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('app')
            ->path('app')
            ->login(Login::class)
            ->registration(Register::class)
            ->tenant(config('filament-team-management.models.team'))
            ->tenantProfile(ManageTeam::class)
            ->discoverResources(
                in: $this->packagePath('src/Filament/App/Resources'),
                for: 'Stats4sd\\FilamentTeamManagement\\Filament\\App\\Resources',
            )
            ->discoverPages(
                in: $this->packagePath('src/Filament/App/Pages'),
                for: 'Stats4sd\\FilamentTeamManagement\\Filament\\App\\Pages',
            )
            ->middleware($this->baseMiddleware())
            ->tenantMiddleware([
                SetLatestTeamMiddleware::class,
            ], isPersistent: true)
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    protected function adminPanel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->discoverResources(
                in: $this->packagePath('src/Filament/Admin/Resources'),
                for: 'Stats4sd\\FilamentTeamManagement\\Filament\\Admin\\Resources',
            )
            ->middleware($this->baseMiddleware())
            ->authMiddleware([
                AuthenticateThroughDefaultPanel::class,
            ]);
    }

    protected function programPanel(Panel $panel): Panel
    {
        return $panel
            ->id('program')
            ->path('program')
            ->tenant(config('filament-team-management.models.program'))
            ->tenantProfile(ManageProgram::class)
            ->discoverPages(
                in: $this->packagePath('src/Filament/Program/Pages'),
                for: 'Stats4sd\\FilamentTeamManagement\\Filament\\Program\\Pages',
            )
            ->middleware($this->baseMiddleware())
            ->tenantMiddleware([
                SetLatestProgramMiddleware::class,
            ], isPersistent: true)
            ->authMiddleware([
                AuthenticateThroughDefaultPanel::class,
            ]);
    }

    /**
     * @return array<class-string>
     */
    protected function baseMiddleware(): array
    {
        return [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            AuthenticateSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            DisableBladeIconComponents::class,
            DispatchServingFilamentEvent::class,
        ];
    }
}
