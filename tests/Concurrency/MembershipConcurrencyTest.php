<?php

namespace Stats4sd\FilamentTeamManagement\Tests\Concurrency;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Stats4sd\FilamentTeamManagement\Actions\AcceptMembershipInvitation;
use Stats4sd\FilamentTeamManagement\Actions\AddMember;
use Stats4sd\FilamentTeamManagement\Actions\DeleteUser;
use Stats4sd\FilamentTeamManagement\Actions\LeaveMembership;
use Stats4sd\FilamentTeamManagement\Actions\LinkTeamToProgram;
use Stats4sd\FilamentTeamManagement\Actions\ResendMembershipInvitation;
use Stats4sd\FilamentTeamManagement\Actions\SendMembershipInvitation;
use Stats4sd\FilamentTeamManagement\Contracts\MembershipParticipant;
use Stats4sd\FilamentTeamManagement\Models\Invite;
use Stats4sd\FilamentTeamManagement\Models\Program;
use Stats4sd\FilamentTeamManagement\Models\Team;
use Stats4sd\FilamentTeamManagement\Support\MembershipContext;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\HostUser as User;
use Stats4sd\FilamentTeamManagement\Tests\TestCase;

class PreserveFinalAdministrator implements MembershipParticipant
{
    public function before(MembershipContext $context): void
    {
        if ($context->operation === 'leave') {
            if (DB::table('host_grants')->where('target_type', $context->target->getTable())->where('target_id', $context->target->getKey())->count() < 2) {
                throw new \RuntimeException('A final administrator must remain.');
            }
            usleep(150000);
        }
    }

    public function after(MembershipContext $context): void
    {
        if ($context->operation === 'leave') {
            DB::table('host_grants')->where('target_type', $context->target->getTable())->where('target_id', $context->target->getKey())->where('user_id', $context->user->getKey())->delete();
        }
    }
}

/** Opt-in: isolated MySQL databases, never an existing application's tables. */
class MembershipConcurrencyTest extends TestCase
{
    protected bool $usePrograms = true;

    private ?string $testDatabase = null;

    protected function setUp(): void
    {
        if (! getenv('FTM_TEST_MYSQL_PORT') || ! extension_loaded('pcntl') || ! extension_loaded('posix') || ! extension_loaded('pdo_mysql')) {
            $this->markTestSkipped('Set FTM_TEST_MYSQL_PORT and enable pdo_mysql, pcntl and posix for isolated MySQL row-lock verification.');
        }
        $database = 'ftm_test_' . bin2hex(random_bytes(8));
        $this->adminConnection()->exec('CREATE DATABASE `' . $database . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->testDatabase = $database;

        try {
            parent::setUp();
            Mail::fake();
        } catch (\Throwable $exception) {
            $this->dropTestDatabase();

            throw $exception;
        }
    }

    private function adminConnection(): \PDO
    {
        $host = getenv('FTM_TEST_MYSQL_HOST') ?: '127.0.0.1';
        $port = getenv('FTM_TEST_MYSQL_PORT');

        return new \PDO("mysql:host={$host};port={$port};charset=utf8mb4", getenv('FTM_TEST_MYSQL_USER') ?: 'root', getenv('FTM_TEST_MYSQL_PASSWORD') ?: null, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
    }

    private function dropTestDatabase(): void
    {
        if ($this->testDatabase !== null) {
            $this->adminConnection()->exec('DROP DATABASE IF EXISTS `' . $this->testDatabase . '`');
            $this->testDatabase = null;
        }
    }

    public function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);
        config()->set('database.connections.testing', [
            'driver' => 'mysql',
            'host' => getenv('FTM_TEST_MYSQL_HOST') ?: '127.0.0.1',
            'port' => getenv('FTM_TEST_MYSQL_PORT'),
            'database' => $this->testDatabase,
            'username' => getenv('FTM_TEST_MYSQL_USER') ?: 'root',
            'password' => getenv('FTM_TEST_MYSQL_PASSWORD') ?: null,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => 'InnoDB',
        ]);
    }

    protected function tearDown(): void
    {
        try {
            try {
                if ($this->testDatabase !== null && $this->app) {
                    foreach (['10_add_program_foreign_keys', '7_create_program_team_table', '6_create_program_members_table', '5_create_programs_table', '9_add_column_to_users_table', '3_create_invites_table', '2_create_team_members_table', '1_create_teams_table'] as $migration) {
                        (include $this->migrationPath($migration))->down();
                    }
                }
            } finally {
                parent::tearDown();
            }
        } finally {
            $this->dropTestDatabase();
        }
    }

    private function race(callable $first, callable $second): array
    {
        $directory = sys_get_temp_dir() . '/ftm-race-' . bin2hex(random_bytes(8));
        mkdir($directory);
        $pids = [];

        try {
            // Each worker must establish its own backend session after the fork.
            DB::disconnect();
            foreach ([$first, $second] as $index => $worker) {
                $pid = pcntl_fork();
                if ($pid === -1) {
                    throw new \RuntimeException('Could not fork a concurrency worker.');
                }
                if ($pid === 0) {
                    try {
                        DB::purge();
                        DB::statement('SET SESSION innodb_lock_wait_timeout = 10');
                        DB::statement('SET SESSION lock_wait_timeout = 10');
                        touch($directory . '/ready-' . $index);
                        $deadline = microtime(true) + 10;
                        while (! file_exists($directory . '/start')) {
                            if (microtime(true) > $deadline) {
                                throw new \RuntimeException('Concurrency worker did not receive the start signal.');
                            }
                            usleep(1000);
                        }
                        $result = ['ok' => true, 'value' => $worker()];
                    } catch (\Throwable $exception) {
                        $result = ['ok' => false, 'exception' => $exception::class, 'message' => $exception->getMessage()];
                    }

                    try {
                        file_put_contents($directory . '/result-' . $index, json_encode($result, JSON_THROW_ON_ERROR));
                        DB::disconnect();
                    } finally {
                        // Avoid inherited PHPUnit shutdown handlers emitting a second report.
                        posix_kill(getmypid(), SIGKILL);
                        exit(0);
                    }
                }
                $pids[] = $pid;
            }
            $deadline = microtime(true) + 10;
            while (! file_exists($directory . '/ready-0') || ! file_exists($directory . '/ready-1')) {
                foreach ([0, 1] as $index) {
                    if (! file_exists($directory . '/ready-' . $index) && file_exists($directory . '/result-' . $index)) {
                        throw new \RuntimeException('Concurrency worker setup failed: ' . file_get_contents($directory . '/result-' . $index));
                    }
                }
                if (microtime(true) > $deadline) {
                    throw new \RuntimeException('Concurrency worker did not become ready.');
                }
                usleep(1000);
            }
            touch($directory . '/start');
            $deadline = microtime(true) + 30;
            while ($pids !== []) {
                foreach ($pids as $index => $pid) {
                    if (pcntl_waitpid($pid, $status, WNOHANG) !== 0) {
                        unset($pids[$index]);
                    }
                }
                if ($pids !== [] && microtime(true) > $deadline) {
                    throw new \RuntimeException('Concurrency workers did not finish within 30 seconds.');
                }
                usleep(1000);
            }
            $results = [];
            foreach ([0, 1] as $index) {
                if (! file_exists($directory . '/result-' . $index)) {
                    throw new \RuntimeException('Concurrency worker ' . $index . ' exited without a result.');
                }
                $results[] = json_decode(file_get_contents($directory . '/result-' . $index), true, flags: JSON_THROW_ON_ERROR);
            }

            return $results;
        } finally {
            foreach ($pids as $pid) {
                posix_kill($pid, SIGKILL);
                pcntl_waitpid($pid, $status);
            }
            foreach (glob($directory . '/*') as $file) {
                unlink($file);
            }
            rmdir($directory);
            DB::purge();
        }
    }

    #[Test]
    public function duplicate_sends_adds_and_links_serialize_on_actual_rows(): void
    {
        $actor = $this->actingAsAdmin();
        $otherActor = User::factory()->create(['host_admin' => true]);
        $team = Team::factory()->create();
        $results = $this->race(
            fn () => app(SendMembershipInvitation::class)->handle($actor, $team, 'same@example.com')->status,
            fn () => app(SendMembershipInvitation::class)->handle($otherActor, $team, 'same@example.com')->status,
        );
        self::assertEqualsCanonicalizing(['invitation_created', 'duplicate_pending'], array_column($results, 'value'), json_encode($results));
        self::assertSame(1, Invite::count());
        $member = User::factory()->create();
        $results = $this->race(
            fn () => app(AddMember::class)->handle($actor, $team, $member),
            fn () => app(AddMember::class)->handle($otherActor, $team, $member),
        );
        self::assertEqualsCanonicalizing([true, false], array_column($results, 'value'), json_encode($results));
        self::assertSame(1, $team->users()->count());
        $program = Program::factory()->create();
        $results = $this->race(
            fn () => app(LinkTeamToProgram::class)->handle($actor, $program, $team),
            fn () => app(LinkTeamToProgram::class)->handle($otherActor, $program, $team),
        );
        self::assertEqualsCanonicalizing([true, false], array_column($results, 'value'), json_encode($results));
        self::assertSame(1, $program->teams()->count());
    }

    #[Test]
    public function resending_and_accepting_the_same_token_cannot_both_succeed(): void
    {
        $actor = $this->actingAsAdmin();
        $team = Team::factory()->create();
        $invite = app(SendMembershipInvitation::class)->handle($actor, $team, 'race@example.com')->invite;
        $results = $this->race(
            fn () => app(ResendMembershipInvitation::class)->handle($actor, $team, $invite)->getKey(),
            fn () => app(AcceptMembershipInvitation::class)->handle($invite->token, ['name' => 'Race', 'email' => $invite->email, 'password' => 'long-password'])->getKey(),
        );
        self::assertSame(1, count(array_filter($results, fn ($result) => $result['ok'])), json_encode($results));
        self::assertSame($invite->fresh()->isAccepted() ? 1 : 0, $team->users()->count());
    }

    #[Test]
    public function account_deletion_serializes_with_addition_to_a_previously_unrelated_target(): void
    {
        $actor = $this->actingAsAdmin();
        $otherActor = User::factory()->create(['host_admin' => true]);
        $member = User::factory()->create();
        $team = Team::factory()->create();
        $results = $this->race(
            fn () => app(DeleteUser::class)->handle($actor, $member),
            fn () => app(AddMember::class)->handle($otherActor, $team, $member),
        );
        self::assertTrue($results[0]['ok'], json_encode($results));
        self::assertNull(User::find($member->getKey()));
        self::assertSame(0, $team->users()->count());
        if (! $results[1]['ok']) {
            self::assertSame(ModelNotFoundException::class, $results[1]['exception']);
        }
    }

    #[Test]
    public function synchronous_host_invariants_prevent_two_concurrent_final_departures(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $team = Team::factory()->create();
        $ordinary = User::factory()->create();
        $team->users()->attach([$first->id, $second->id, $ordinary->id]);
        $first->grant($team);
        $second->grant($team);
        config()->set('filament-team-management.participants', [PreserveFinalAdministrator::class]);
        $results = $this->race(
            fn () => app(LeaveMembership::class)->handle($first, $team),
            fn () => app(LeaveMembership::class)->handle($second, $team),
        );
        self::assertSame(1, count(array_filter($results, fn ($result) => $result['ok'])), json_encode($results));
        self::assertSame(2, $team->users()->count());
        self::assertTrue($team->users()->whereKey($ordinary->getKey())->exists());
        self::assertSame(1, DB::table('host_grants')->count());
    }
}
