<?php

use Illuminate\Support\Facades\File;
use Stats4sd\FilamentTeamManagement\Commands\InstallFilamentTeamManagement;

/*
 * The install command writes into the (shared) Testbench skeleton via base_path()
 * and app()->environmentFile(). We snapshot every path it can touch and restore
 * it afterwards so the skeleton is left exactly as we found it.
 */

beforeEach(function () {
    $this->touched = [
        base_path('.env'),
        base_path('.env.example'),
        base_path('database/seeders/DatabaseSeeder.php'),
    ];

    $this->backups = [];
    foreach ($this->touched as $path) {
        $this->backups[$path] = File::exists($path) ? File::get($path) : null;
    }

    // Snapshot the migrations directory so published stubs can be removed.
    File::ensureDirectoryExists(base_path('database/migrations'));
    $this->existingMigrations = collect(File::files(base_path('database/migrations')))
        ->map->getFilename()
        ->all();

    File::ensureDirectoryExists(base_path('database/seeders'));
    File::put(base_path('.env'), "APP_NAME=Testing\n");
});

afterEach(function () {
    foreach ($this->backups as $path => $contents) {
        if ($contents === null) {
            File::delete($path);
        } else {
            File::put($path, $contents);
        }
    }

    // Drop any migration files the command published.
    foreach (File::files(base_path('database/migrations')) as $file) {
        if (! in_array($file->getFilename(), $this->existingMigrations, true)) {
            File::delete($file->getPathname());
        }
    }
});

function runInstall(bool $usePrograms)
{
    return test()->artisan('filament-team-management:install')
        ->expectsConfirmation('Do you want to continue?', 'yes')
        ->expectsConfirmation('Do you want to use "programs" (groups of teams)?', $usePrograms ? 'yes' : 'no')
        ->expectsConfirmation('Do you need the roles and permissions tables from the Spatie Permissions package?', 'no')
        ->expectsConfirmation('Do you want to run the migrations now?', 'no')
        ->expectsConfirmation('Do you want to add the recommended package seeders to your DatabaseSeeder file?', 'no')
        ->assertSuccessful();
}

function publishedMigrations(array $existing): string
{
    return collect(File::files(base_path('database/migrations')))
        ->map->getFilename()
        ->reject(fn ($name) => in_array($name, $existing, true))
        ->implode(' ');
}

it('publishes only the default migrations without programs', function () {
    runInstall(usePrograms: false);

    $published = publishedMigrations($this->existingMigrations);

    expect($published)->toContain('create_teams_table')
        ->and($published)->toContain('create_invites_table')
        ->and($published)->not->toContain('create_programs_table');
});

it('publishes the program migrations when opted in', function () {
    runInstall(usePrograms: true);

    $published = publishedMigrations($this->existingMigrations);

    expect($published)->toContain('create_teams_table')
        ->and($published)->toContain('create_programs_table')
        ->and($published)->toContain('create_program_members_table');
});

it('injects the package seeders inside the run() method without corrupting the file', function () {
    $original = <<<'PHP'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SomeExistingSeeder::class);
    }
}
PHP;

    File::put(base_path('database/seeders/DatabaseSeeder.php'), $original);

    (new InstallFilamentTeamManagement)->updateDatabaseSeeder(usePrograms: true);

    $result = File::get(base_path('database/seeders/DatabaseSeeder.php'));

    // both package seeders injected, original call preserved
    expect($result)->toContain('SomeExistingSeeder::class')
        ->and($result)->toContain('Stats4sd\FilamentTeamManagement\Database\Seeders\DatabaseSeeder::class')
        ->and($result)->toContain('Stats4sd\FilamentTeamManagement\Database\Seeders\DatabaseProgramSeeder::class');

    // file is still syntactically valid and braces remain balanced
    expect(substr_count($result, '{'))->toBe(substr_count($result, '}'))
        ->and(token_get_all($result, TOKEN_PARSE))->toBeArray();
});
