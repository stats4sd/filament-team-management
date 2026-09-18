<?php

use Dotenv\Dotenv;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\File;
use Stats4sd\FilamentTeamManagement\Commands\InstallFilamentTeamManagement;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\ProjectTeam;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

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
        ->expectsConfirmation('Do you want to run the migrations now?', 'no')
        ->expectsConfirmation('Do you want to add the recommended package seeders to your DatabaseSeeder file?', 'no')
        ->expectsConfirmation('Publish deny-default host policy stubs?', 'no')
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

it('round trips namespace values through dotenv and treats each env file independently', function () {
    File::put(base_path('.env'), "FILAMENT_TEAM_MANAGEMENT_QUEUE_MAIL=false\nAPP_NAME=Test\n");
    File::put(base_path('.env.example'), "FILAMENT_TEAM_MANAGEMENT_TEAM_MODEL='Example\\Existing'\n");
    runInstall(true);
    runInstall(true);
    $actual = Dotenv::parse(File::get(base_path('.env')));
    $example = Dotenv::parse(File::get(base_path('.env.example')));
    expect($actual['FILAMENT_TEAM_MANAGEMENT_TEAM_MODEL'])->toBe(config('filament-team-management.models.team'))
        ->and($actual['FILAMENT_TEAM_MANAGEMENT_QUEUE_MAIL'])->toBe('false')
        ->and($example['FILAMENT_TEAM_MANAGEMENT_TEAM_MODEL'])->toBe('Example\\Existing')
        ->and($example['FILAMENT_TEAM_MANAGEMENT_USER_MODEL'])->toBe(config('auth.providers.users.model'))
        ->and(substr_count(File::get(base_path('.env')), 'FILAMENT_TEAM_MANAGEMENT_QUEUE_MAIL='))->toBe(1);
});

it('inserts seed calls idempotently around comments strings interpolation and nested functions', function () {
    $source = <<<'PHP'
<?php
class DatabaseSeeder {
    public function run () : void {
        // a closing } is not syntax
        $value = '{';
        echo "{$value}";
        $callback = function () { return '}'; };
    }
}
PHP;
    $path = base_path('database/seeders/DatabaseSeeder.php');
    File::put($path, $source);
    $command = new InstallFilamentTeamManagement;
    $command->updateDatabaseSeeder(true);
    $command->updateDatabaseSeeder(true);
    $result = File::get($path);
    expect(token_get_all($result, TOKEN_PARSE))->toBeArray()
        ->and(substr_count($result, 'DatabaseProgramSeeder::class'))->toBe(1)
        ->and(substr_count($result, 'DatabaseSeeder::class'))->toBe(1)
        ->and($result)->toContain('        $this->call(');
});

it('does not rewrite unsupported static or missing seeder run methods', function (string $source) {
    $path = base_path('database/seeders/DatabaseSeeder.php');
    File::put($path, $source);
    $command = new InstallFilamentTeamManagement;
    $command->setOutput(new OutputStyle(new ArrayInput([]), new BufferedOutput));
    $command->updateDatabaseSeeder(false);
    expect(File::get($path))->toBe($source);
})->with([
    '<?php class DatabaseSeeder { public static function run(): void {} }',
    '<?php function run(): void {} class DatabaseSeeder {}',
    '<?php abstract class DatabaseSeeder { abstract public function run(): void; }',
]);

it('publishes configured deny-default policies without overwriting host files', function () {
    $path = app_path('Policies/TeamPolicy.php');
    $programPath = app_path('Policies/ProgramPolicy.php');
    $before = File::exists($path) ? File::get($path) : null;
    $programBefore = File::exists($programPath) ? File::get($programPath) : null;
    $command = new InstallFilamentTeamManagement;
    $command->setOutput(new OutputStyle(new ArrayInput([]), new BufferedOutput));

    try {
        File::delete([$path, $programPath]);
        config()->set('filament-team-management.models.team', ProjectTeam::class);
        $command->publishPolicies(false);
        $policy = File::get($path);
        expect($policy)->toContain('ProjectTeam $target')->toContain('return false;')
            ->and(token_get_all($policy, TOKEN_PARSE))->toBeArray()->and(File::exists($programPath))->toBeFalse();
        File::put($path, '<?php // Host policy');
        $command->publishPolicies(true);
        expect(File::get($path))->toBe('<?php // Host policy')->and(File::exists($programPath))->toBeTrue();
    } finally {
        $before === null ? File::delete($path) : File::put($path, $before);
        $programBefore === null ? File::delete($programPath) : File::put($programPath, $programBefore);
    }
});
