<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @return array<int, string> Column names that carry a foreign-key constraint on $table.
 */
function foreignKeyColumns(string $table): array
{
    return collect(Schema::getForeignKeys($table))->pluck('columns')->flatten()->all();
}

it('creates the default tables and the user latest_team_id column', function () {
    expect(Schema::hasTable('teams'))->toBeTrue()
        ->and(Schema::hasTable('team_members'))->toBeTrue()
        ->and(Schema::hasTable('invites'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'latest_team_id'))->toBeTrue();
});

it('does not create the program tables without programs', function () {
    expect(Schema::hasTable('programs'))->toBeFalse();
});

// The program columns are always created so the schema never depends on use_programs at
// migration time; only the foreign keys (stub 10, program tag) are program-only.
it('creates the program columns without foreign keys when programs are disabled', function () {
    expect(Schema::hasColumn('invites', 'program_id'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'latest_program_id'))->toBeTrue()
        ->and(foreignKeyColumns('invites'))->not->toContain('program_id')
        ->and(foreignKeyColumns('users'))->not->toContain('latest_program_id');
});

// Stub 3 used bare constrained(), which guesses the referenced table from the column name
// (team_id -> teams, role_id -> roles) and so broke under a custom teams table or Spatie roles
// table. It now names both tables from config. Under default names the two are indistinguishable,
// so re-run the stub against non-default table names and check where the keys point.
it('constrains invites.team_id and invites.role_id against the configured (non-default) tables', function () {
    Schema::create('custom_teams', fn (Blueprint $table) => $table->id());
    Schema::create('custom_roles', fn (Blueprint $table) => $table->id());
    config()->set('filament-team-management.table_names.teams', 'custom_teams');
    config()->set('permission.table_names.roles', 'custom_roles');

    Schema::drop('invites');
    $migration = include dirname(__DIR__, 2) . '/database/migrations/3_create_invites_table.php.stub';
    $migration->up();

    $foreignTables = collect(Schema::getForeignKeys('invites'))
        ->mapWithKeys(fn (array $fk) => [$fk['columns'][0] => $fk['foreign_table']]);

    expect($foreignTables->get('team_id'))->toBe('custom_teams')
        ->and($foreignTables->get('role_id'))->toBe('custom_roles');
});
