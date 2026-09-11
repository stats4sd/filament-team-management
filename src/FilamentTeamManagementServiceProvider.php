<?php

namespace Stats4sd\FilamentTeamManagement;

use Carbon\Carbon;
use Filament\Support\Assets\Asset;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Stats4sd\FilamentTeamManagement\Commands\InstallFilamentTeamManagement;
use Stats4sd\FilamentTeamManagement\Testing\TestsFilamentTeamManagement;

class FilamentTeamManagementServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-team-management';

    public static string $viewNamespace = 'filament-team-management';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasCommands($this->getCommands());

        $package->hasConfigFile();

        $package->hasTranslations();
        $package->hasViews(static::$viewNamespace);
    }

    public function packageRegistered(): void {}

    /**
     * @throws \ReflectionException
     */
    public function packageBooted(): void
    {
        // Migrations are handled in a custom way, split into a default and a program publish tag.
        // A single clock is shared across both tags so every published program migration gets a
        // timestamp strictly after every default one: 10_add_program_foreign_keys must run after
        // stubs 3 and 9 have created the columns it constrains.
        $now = Carbon::now();

        $this->handleMigrations($this->getDefaultMigrations(), 'default', $now);
        $this->handleMigrations($this->getProgramMigrations(), 'program', $now);

        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/filament-team-management/{$file->getFilename()}"),
                ], 'filament-team-management-stubs');
            }
        }

        // Testing
        Testable::mixin(new TestsFilamentTeamManagement);
    }

    protected function getAssetPackageName(): ?string
    {
        return 'stats4sd/filament-team-management';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            InstallFilamentTeamManagement::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getDefaultMigrations(): array
    {
        return [
            '1_create_teams_table',
            '2_create_team_members_table',
            '3_create_invites_table',
            '9_add_column_to_users_table',
        ];
    }

    /**
     * @return array<string>
     */
    protected function getProgramMigrations(): array
    {
        return [
            '5_create_programs_table',
            '6_create_program_members_table',
            '7_create_program_team_table',
            '10_add_program_foreign_keys',
        ];
    }

    /**
     * @param  array<string>  $migrations
     * @param  Carbon  $now  Shared, mutable clock: each published file advances it by one second.
     */
    protected function handleMigrations(array $migrations, string $tag, Carbon $now): void
    {
        foreach ($migrations as $migrationFileName) {

            $filePath = $this->package->basePath("/../database/migrations/{$migrationFileName}.php");
            if (! file_exists($filePath)) {
                // Support for the .stub file extension
                $filePath .= '.stub';
            }

            $this->publishes([
                $filePath => $this->generateMigrationName(
                    $migrationFileName,
                    $now->addSecond()
                ), ], "{$this->package->shortName()}-migrations-{$tag}");

            if ($this->package->runsMigrations) {
                $this->loadMigrationsFrom($filePath);
            }
        }
    }
}
