<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Stats4sd\FilamentTeamManagement\Actions\AcceptMembershipInvitation;
use Stats4sd\FilamentTeamManagement\Actions\SendMembershipInvitation;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\HostUser;

it('migrates custom tables and keys in both modes and reverses the fresh schema', function (bool $programs) {
    config()->set('database.connections.custom_schema', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]);
    config()->set('database.default', 'custom_schema');
    config()->set('filament-team-management.use_programs', $programs);
    config()->set('filament-team-management.table_names', [
        'users' => 'people', 'teams' => 'sites', 'programs' => 'initiatives', 'invites' => 'membership_requests',
        'team_members' => 'site_people', 'program_members' => 'initiative_people', 'program_team' => 'initiative_sites',
    ]);
    config()->set('filament-team-management.column_names', ['users_foreign_key' => 'person_key', 'teams_foreign_key' => 'site_key', 'programs_foreign_key' => 'initiative_key']);
    Schema::create('people', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->timestamp('email_verified_at')->nullable();
        $table->rememberToken();
        $table->boolean('host_admin')->default(false);
        $table->timestamps();
    });
    $names = ['1_create_teams_table', '2_create_team_members_table', '3_create_invites_table', '9_add_column_to_users_table'];
    if ($programs) {
        array_push($names, '5_create_programs_table', '6_create_program_members_table', '7_create_program_team_table', '10_add_program_foreign_keys');
    }
    $migrations = [];
    foreach ($names as $name) {
        $migration = include dirname(__DIR__, 2) . '/database/migrations/' . $name . '.php.stub';
        $migration->up();
        $migrations[] = $migration;
    }
    Mail::fake();
    $actor = HostUser::factory()->create(['host_admin' => true]);
    $target = config('filament-team-management.models.' . ($programs ? 'program' : 'team'))::create(['name' => 'Custom target']);
    $invite = app(SendMembershipInvitation::class)->handle($actor, $target, 'new@example.test')->invite;
    $user = app(AcceptMembershipInvitation::class)->handle($invite->token, ['name' => 'New member', 'email' => 'new@example.test', 'password' => 'long-password']);
    expect($target->users()->whereKey($user->getKey())->exists())->toBeTrue()->and($invite->getTable())->toBe('membership_requests');
    $existing = HostUser::factory()->create();
    expect(app(SendMembershipInvitation::class)->handle($actor, $target, $existing->email)->status)->toBe('member_added');
    expect(Schema::hasColumn('membership_requests', 'role_id'))->toBeFalse()->and(Schema::hasColumn('site_people', 'is_admin'))->toBeFalse();
    foreach (array_reverse($migrations) as $migration) {
        $migration->down();
    }
    expect(Schema::hasTable('membership_requests'))->toBeFalse()->and(Schema::hasTable('sites'))->toBeFalse()
        ->and(Schema::hasColumn('people', 'latest_team_id'))->toBeFalse();
    DB::purge('custom_schema');
    config()->set('database.default', 'testing');
})->with([false, true]);
