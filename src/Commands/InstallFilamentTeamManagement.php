<?php

namespace Stats4sd\FilamentTeamManagement\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Stats4sd\FilamentTeamManagement\Models\Program;
use Stats4sd\FilamentTeamManagement\Models\Team;

class InstallFilamentTeamManagement extends Command
{
    public $signature = 'filament-team-management:install';

    public $description = 'Runs the setup script for Filament Team Management package';

    public function handle(): int
    {
        // confirm continue;
        $this->info('It is recommended to make a git commit before running this command. That way you can easily review the git diff to see what changes were made by the script.');
        if (! $this->confirm('Do you want to continue?')) {
            return self::SUCCESS;
        }

        // Ask do you want to use 'programs' (groups of teams)?

        $usePrograms = $this->confirm('Do you want to use "programs" (groups of teams)?');

        // set .env variable
        $this->updateEnv($usePrograms);

        $this->info('This package requires the Spatie Permissions package. If you have not already installed it and published the migrations, please do so now:');
        if ($this->confirm('Do you need the roles and permissions tables from the Spatie Permissions package?', true)) {

            $this->call('vendor:publish', [
                '--provider' => "Spatie\Permission\PermissionServiceProvider",
            ]);
        }

        $this->info('publishing default migrations');
        $this->call('vendor:publish', [
            '--provider' => "Stats4sd\FilamentTeamManagement\FilamentTeamManagementServiceProvider",
            '--tag' => 'filament-team-management-migrations-default',
        ]);

        if ($usePrograms) {

            $this->info('publishing program-related migrations');
            $this->call('vendor:publish', [
                '--provider' => "Stats4sd\FilamentTeamManagement\FilamentTeamManagementServiceProvider",
                '--tag' => 'filament-team-management-migrations-program',
            ]);

        }

        if ($this->confirm('Do you want to run the migrations now?')) {
            $this->call('migrate');
        }

        if ($this->confirm('Do you want to add the recommended package seeders to your DatabaseSeeder file?')) {
            $this->updateDatabaseSeeder($usePrograms);
        }

        $this->info('Installation complete!');
        $this->info('Please make sure that your User model extends the FilamentTeamManagement User model.');

        return self::SUCCESS;
    }

    public function updateDatabaseSeeder(bool $usePrograms): void
    {
        // find the DatabaseSeeder file and add the seeders
        $databaseSeederPath = base_path('database/seeders/DatabaseSeeder.php');

        if (! file_exists($databaseSeederPath)) {
            $this->error('DatabaseSeeder.php file not found. Please add the following seeders to your seeder file manually:');

            $this->comment('Stats4sd\FilamentTeamManagement\Database\Seeders\DatabaseSeeder::class');

            return;
        }

        $databaseSeederContents = File::get($databaseSeederPath);

        // find the end of the run() method
        $runMethodStartPos = strpos($databaseSeederContents, 'public function run()');

        if ($runMethodStartPos === false) {
            $this->error('Could not find the run() method in DatabaseSeeder.php. Please add the following seeders to your seeder file manually:');
            $this->comment('Stats4sd\FilamentTeamManagement\Database\Seeders\DatabaseSeeder::class');

            return;
        }

        // keep track of open and closing curly braces
        $runMethodStartBrace = strpos($databaseSeederContents, '{', $runMethodStartPos);

        if ($runMethodStartBrace === false) {
            $this->error('Could not find the run() method in DatabaseSeeder.php. Please add the following seeders to your seeder file manually:');
            $this->comment('Stats4sd\FilamentTeamManagement\Database\Seeders\DatabaseSeeder::class');

            return;
        }

        $braceStack = [];
        $length = strlen($databaseSeederContents);

        // go through the entire function until we get to the end } (keeping track of open and closing braces)
        for ($i = $runMethodStartBrace; $i < $length; $i++) {
            if ($databaseSeederContents[$i] === '{') {
                $braceStack[] = '{';
            } elseif ($databaseSeederContents[$i] === '}') {
                array_pop($braceStack);
            }

            if (empty($braceStack)) {
                $runMethodEndPos = $i;

                break;
            }
        }

        if (! isset($runMethodEndPos)) {
            $this->error('Could not find the end of the run() method in DatabaseSeeder.php. Please add the following seeders to your seeder file manually:');
            $this->comment('\Stats4sd\FilamentTeamManagement\Database\Seeders\DatabaseSeeder::class');

            if ($usePrograms) {
                $this->comment('\Stats4sd\FilamentTeamManagement\Database\Seeders\DatabaseProgramSeeder::class');
            }

            return;
        }

        if ($usePrograms) {
            $databaseSeederContents = substr_replace($databaseSeederContents, '$this->call(\Stats4sd\FilamentTeamManagement\Database\Seeders\DatabaseProgramSeeder::class);' . PHP_EOL, $runMethodEndPos, 0);
        }

        // add the seeders to the run() method
        $databaseSeederContents = substr_replace($databaseSeederContents, PHP_EOL . PHP_EOL . '$this->call(\Stats4sd\FilamentTeamManagement\Database\Seeders\DatabaseSeeder::class);' . PHP_EOL, $runMethodEndPos, 0);

        file_put_contents($databaseSeederPath, $databaseSeederContents);

    }

    // copied shamelessly from the Laravel Reverb Install command
    private function updateEnv($usePrograms): void
    {
        if (File::missing($env = app()->environmentFile())) {
            $this->warn('.env file not found. Please add the following variables to your .env file manually:');

            return;
        }

        $contents = File::get($env);

        $useProgramsString = $usePrograms ? 'true' : 'false';

        $teamClass = Team::class;
        $programClass = Program::class;
        $userClass = config('auth.providers.users.model');

        $teamTable = (new $teamClass)->getTable();
        $programTable = (new $programClass)->getTable();
        $userTable = (new $userClass)->getTable();

        $teamForeignKey = Str::singular($userTable) . '_id';
        $programForeignKey = Str::singular($teamTable) . '_id';
        $userForeignKey = Str::singular($userTable) . '_id';

        $teamManagementMembersTable = Str::snake(Str::singular($teamTable)) . '_members';
        $programManagementMembersTable = Str::snake(Str::singular($programTable)) . '_members';
        $programTeamTable = Str::snake(Str::singular($programTable)) . '_' . Str::snake(Str::singular($teamTable));

        $roleModel = config('permission.models.role');

        $variables = [
            'FILAMENT_TEAM_MANAGEMENT_USE_PROGRAMS' => "FILAMENT_TEAM_MANAGEMENT_USE_PROGRAMS={$useProgramsString}",

            'FILAMENT_TEAM_MANAGEMENT_USER_MODEL' => "FILAMENT_TEAM_MANAGEMENT_USER_MODEL={$userClass}",
            'FILAMENT_TEAM_MANAGEMENT_USER_TABLE' => "FILAMENT_TEAM_MANAGEMENT_USER_TABLE={$userTable}",
            'FILAMENT_TEAM_MANAGEMENT_USER_FOREIGN_KEY' => "FILAMENT_TEAM_MANAGEMENT_USER_FOREIGN_KEY={$userForeignKey}",

            'FILAMENT_TEAM_MANAGEMENT_TEAM_MODEL' => "FILAMENT_TEAM_MANAGEMENT_TEAM_MODEL={$teamClass}",
            'FILAMENT_TEAM_MANAGEMENT_TEAMS_TABLE' => "FILAMENT_TEAM_MANAGEMENT_TEAMS_TABLE={$teamTable}",
            'FILAMENT_TEAM_MANAGEMENT_TEAMS_FOREIGN_KEY' => "FILAMENT_TEAM_MANAGEMENT_TEAMS_FOREIGN_KEY={$teamForeignKey}",

            'FILAMENT_TEAM_MANAGEMENT_TEAM_MEMBERS_TABLE' => "FILAMENT_TEAM_MANAGEMENT_TEAM_MEMBERS_TABLE={$teamManagementMembersTable}",

        ];

        if ($usePrograms) {
            $variables['FILAMENT_TEAM_MANAGEMENT_PROGRAM_MODEL'] = "FILAMENT_TEAM_MANAGEMENT_PROGRAM_MODEL={$programClass}";
            $variables['FILAMENT_TEAM_MANAGEMENT_PROGRAMS_TABLE'] = "FILAMENT_TEAM_MANAGEMENT_PROGRAMS_TABLE={$programTable}";
            $variables['FILAMENT_TEAM_MANAGEMENT_PROGRAMS_FOREIGN_KEY'] = "FILAMENT_TEAM_MANAGEMENT_PROGRAMS_FOREIGN_KEY={$programForeignKey}";
            $variables['FILAMENT_TEAM_MANAGEMENT_PROGRAM_MEMBERS_TABLE'] = "FILAMENT_TEAM_MANAGEMENT_PROGRAM_MEMBERS_TABLE={$programManagementMembersTable}";
            $variables['FILAMENT_TEAM_MANAGEMENT_PROGRAM_TEAM_TABLE'] = "FILAMENT_TEAM_MANAGEMENT_PROGRAM_TEAM_TABLE={$programTeamTable}";
        }

        $variables = Arr::where($variables, function ($value, $key) use ($contents) {
            return ! Str::contains($contents, PHP_EOL . $key); // check if the key is already in the .env file
        });

        $variables = trim(implode(PHP_EOL, $variables));

        File::append(
            $env,
            Str::endsWith($contents, PHP_EOL) ? PHP_EOL . $variables . PHP_EOL : PHP_EOL . PHP_EOL . $variables . PHP_EOL,
        );

        // find and update env.example file
        $envExample = base_path('.env.example');
        if (file_exists($envExample)) {
            $contents = File::get($envExample);

            File::append(
                $envExample,
                Str::endsWith($contents, PHP_EOL) ? PHP_EOL . $variables . PHP_EOL : PHP_EOL . PHP_EOL . $variables . PHP_EOL,
            );
        }

    }
}
