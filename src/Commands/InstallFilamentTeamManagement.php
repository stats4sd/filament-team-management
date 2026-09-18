<?php

namespace Stats4sd\FilamentTeamManagement\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Stats4sd\FilamentTeamManagement\FilamentTeamManagementServiceProvider;

class InstallFilamentTeamManagement extends Command
{
    public $signature = 'filament-team-management:install';

    public $description = 'Install membership tables and optional host policy stubs';

    public function handle(): int
    {
        $this->info('Commit your work first so you can review the installation changes.');
        if (! $this->confirm('Do you want to continue?')) {
            return self::SUCCESS;
        }
        $programs = $this->confirm('Do you want to use "programs" (groups of teams)?');
        $this->updateEnv($programs);
        foreach ($programs ? ['default', 'program'] : ['default'] as $tag) {
            $this->call('vendor:publish', ['--provider' => FilamentTeamManagementServiceProvider::class, '--tag' => 'filament-team-management-migrations-' . $tag]);
        }
        if ($this->confirm('Do you want to run the migrations now?')) {
            $this->call('migrate');
        }
        if ($this->confirm('Do you want to add the recommended package seeders to your DatabaseSeeder file?')) {
            $this->updateDatabaseSeeder($programs);
        }
        if ($this->confirm('Publish deny-default host policy stubs?')) {
            $this->publishPolicies($programs);
        }
        $this->info('Installation complete. Register host policies and implement User panel/tenant access before enabling management. See SETUP.md.');

        return self::SUCCESS;
    }

    public function publishPolicies(bool $programs): void
    {
        foreach ($programs ? ['team', 'program'] : ['team'] as $type) {
            $path = app_path('Policies/' . ucfirst($type) . 'Policy.php');
            if (File::exists($path)) {
                $this->warn($path . ' already exists; preserved.');

                continue;
            }
            $stub = File::get(__DIR__ . '/../../stubs/MembershipPolicy.php.stub');
            $stub = str_replace(['{{ policy }}', '{{ target }}', '{{ user }}', '{{ counterpart }}', '{{ link }}'], [ucfirst($type) . 'Policy', '\\' . ltrim(config('filament-team-management.models.' . $type), '\\'), '\\' . ltrim(config('filament-team-management.models.user'), '\\'), '\\' . ltrim(config('filament-team-management.models.' . ($type === 'team' ? 'program' : 'team')), '\\'), $type === 'team' ? 'Program' : 'Team'], $stub);
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $stub);
        }
    }

    public function updateDatabaseSeeder(bool $usePrograms): void
    {
        $path = database_path('seeders/DatabaseSeeder.php');
        if (! File::exists($path)) {
            $this->warn('DatabaseSeeder.php not found; no seeder changes made.');

            return;
        }
        $source = File::get($path);

        try {
            $tokens = token_get_all($source, TOKEN_PARSE);
        } catch (\ParseError) {
            $this->warn('DatabaseSeeder.php is not valid PHP; no changes made.');

            return;
        }
        $end = $this->findRunEnd($tokens);
        if ($end === null) {
            $this->warn('Could not identify a supported run() method; no changes made.');

            return;
        }
        $code = implode('', array_map(fn ($token) => is_array($token) ? (in_array($token[0], [T_COMMENT, T_DOC_COMMENT, T_CONSTANT_ENCAPSED_STRING], true) ? '' : $token[1]) : $token, $tokens));
        $calls = [];
        foreach ($usePrograms ? ['DatabaseSeeder', 'DatabaseProgramSeeder'] : ['DatabaseSeeder'] as $class) {
            $name = '\\Stats4sd\\FilamentTeamManagement\\Database\\Seeders\\' . $class;
            if (! str_contains($code, $name . '::class')) {
                $calls[] = '        $this->call(' . $name . '::class);';
            }
        }
        if ($calls) {
            $before = rtrim(substr($source, 0, $end));
            File::put($path, $before . "\n\n" . implode("\n", $calls) . "\n    " . substr($source, $end));
        }
    }

    /** Locate the actual non-static DatabaseSeeder::run body without interpreting strings/comments as braces. */
    private function findRunEnd(array $tokens): ?int
    {
        $offsets = [];
        $offset = 0;
        foreach ($tokens as $index => $token) {
            $offsets[$index] = $offset;
            $offset += strlen(is_array($token) ? $token[1] : $token);
        }
        $depth = 0;
        $classDepth = null;
        $awaitingClass = false;
        foreach ($tokens as $index => $token) {
            if (is_array($token) && $token[0] === T_CLASS) {
                for ($next = $index + 1; isset($tokens[$next]); $next++) {
                    $name = $tokens[$next];
                    if (is_array($name) && in_array($name[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                        continue;
                    }
                    $awaitingClass = is_array($name) && $name[0] === T_STRING && $name[1] === 'DatabaseSeeder';

                    break;
                }
            }
            if ($token === '{' || (is_array($token) && in_array($token[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))) {
                $depth++;
                if ($awaitingClass) {
                    $classDepth = $depth;
                    $awaitingClass = false;
                }
            } elseif ($token === '}') {
                if ($classDepth === $depth) {
                    $classDepth = null;
                }
                $depth--;
            }
            if (! is_array($token) || $token[0] !== T_FUNCTION || $depth !== $classDepth) {
                continue;
            }
            for ($next = $index + 1; isset($tokens[$next]); $next++) {
                $name = $tokens[$next];
                if (is_array($name) && in_array($name[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG], true)) {
                    continue;
                }
                if (! is_array($name) || $name[0] !== T_STRING || strtolower($name[1]) !== 'run') {
                    break;
                }
                for ($previous = $index - 1; $previous >= 0; $previous--) {
                    $modifier = $tokens[$previous];
                    if (in_array($modifier, ['{', '}', ';'], true)) {
                        break;
                    }
                    if (is_array($modifier) && $modifier[0] === T_STATIC) {
                        return null;
                    }
                }
                $bodyDepth = 0;
                for ($body = $next + 1; isset($tokens[$body]); $body++) {
                    $part = $tokens[$body];
                    if ($part === ';' && $bodyDepth === 0) {
                        return null;
                    }
                    if ($part === '{' || (is_array($part) && in_array($part[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))) {
                        $bodyDepth++;
                    }
                    if ($part === '}' && --$bodyDepth === 0) {
                        return $offsets[$body];
                    }
                }

                return null;
            }
        }

        return null;
    }

    private function updateEnv(bool $usePrograms): void
    {
        $variables = ['FILAMENT_TEAM_MANAGEMENT_USE_PROGRAMS' => $usePrograms ? 'true' : 'false', 'FILAMENT_TEAM_MANAGEMENT_QUEUE_MAIL' => config('filament-team-management.queue_mail') ? 'true' : 'false'];
        $map = [
            'USER_MODEL' => 'models.user', 'TEAM_MODEL' => 'models.team', 'PROGRAM_MODEL' => 'models.program',
            'USER_TABLE' => 'table_names.users', 'TEAMS_TABLE' => 'table_names.teams', 'PROGRAMS_TABLE' => 'table_names.programs', 'INVITES_TABLE' => 'table_names.invites',
            'TEAM_MEMBERS_TABLE' => 'table_names.team_members', 'PROGRAM_MEMBERS_TABLE' => 'table_names.program_members', 'PROGRAM_TEAM_TABLE' => 'table_names.program_team',
            'USER_FOREIGN_KEY' => 'column_names.users_foreign_key', 'TEAMS_FOREIGN_KEY' => 'column_names.teams_foreign_key', 'PROGRAMS_FOREIGN_KEY' => 'column_names.programs_foreign_key',
        ];
        foreach ($map as $key => $config) {
            $value = (string) config('filament-team-management.' . $config);
            if ($key === 'USER_MODEL') {
                $value = config('auth.providers.users.model', $value);
            }
            // Single-quoted dotenv values preserve namespace backslashes literally.
            $variables['FILAMENT_TEAM_MANAGEMENT_' . $key] = "'" . str_replace("'", "\\'", $value) . "'";
        }
        foreach ([app()->environmentFilePath(), base_path('.env.example')] as $path) {
            if (! File::exists($path)) {
                $this->warn(basename($path) . ' not found; preserved.');

                continue;
            }
            $contents = File::get($path);
            $lines = [];
            foreach ($variables as $key => $value) {
                if (! preg_match('/^\s*(?:export\s+)?' . preg_quote($key, '/') . '\s*=/m', $contents)) {
                    $lines[] = $key . '=' . $value;
                }
            }
            if ($lines) {
                File::put($path, rtrim($contents) . "\n\n" . implode("\n", $lines) . "\n");
            }
        }
    }
}
