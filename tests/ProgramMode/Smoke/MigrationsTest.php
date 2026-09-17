<?php

use Illuminate\Support\Facades\Schema;

it('creates the program tables and the program columns in program mode', function () {
    expect(Schema::hasTable('programs'))->toBeTrue()
        ->and(Schema::hasTable('program_members'))->toBeTrue()
        ->and(Schema::hasTable('program_team'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'latest_program_id'))->toBeTrue()
        ->and(Schema::hasColumn('invites', 'program_id'))->toBeTrue();
});

// Stub 10 (program tag) adds the constraints the default stubs deliberately leave off, with the
// same cascade semantics the guarded 4.x stubs had.
it('constrains the program columns against the programs table in program mode', function () {
    $inviteFk = collect(Schema::getForeignKeys('invites'))->firstWhere('columns', ['program_id']);
    $userFk = collect(Schema::getForeignKeys('users'))->firstWhere('columns', ['latest_program_id']);

    expect($inviteFk)->not->toBeNull()
        ->and($inviteFk['foreign_table'])->toBe('programs')
        ->and($inviteFk['on_delete'])->toBe('cascade')
        ->and($userFk)->not->toBeNull()
        ->and($userFk['foreign_table'])->toBe('programs')
        ->and($userFk['on_delete'])->toBe('set null');
});

// A 4.x database with programs enabled already has both constraints (the guarded stubs created
// them inline). Re-running the installer publishes stub 10 into such an app, so it must be a no-op
// rather than a duplicate-constraint failure.
it('skips constraints that already exist when stub 10 runs again', function () {
    $migration = include dirname(__DIR__, 3) . '/database/migrations/10_add_program_foreign_keys.php.stub';
    $migration->up();

    $count = fn (string $table, string $column) => collect(Schema::getForeignKeys($table))
        ->filter(fn (array $fk) => $fk['columns'] === [$column])
        ->count();

    expect($count('invites', 'program_id'))->toBe(1)
        ->and($count('users', 'latest_program_id'))->toBe(1);
});

it('rolls back stub 10 by dropping only the two program foreign keys', function () {
    $migration = include dirname(__DIR__, 3) . '/database/migrations/10_add_program_foreign_keys.php.stub';
    $migration->down();

    $fkColumns = fn (string $table) => collect(Schema::getForeignKeys($table))->pluck('columns')->flatten()->all();

    expect($fkColumns('invites'))->not->toContain('program_id')
        ->and($fkColumns('invites'))->toContain('team_id')
        ->and($fkColumns('users'))->not->toContain('latest_program_id')
        ->and($fkColumns('users'))->toContain('latest_team_id')
        ->and(Schema::hasColumn('invites', 'program_id'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'latest_program_id'))->toBeTrue();
});
