<?php

use Illuminate\Support\Facades\Schema;

it('creates the default tables and the user latest_team_id column', function () {
    expect(Schema::hasTable('teams'))->toBeTrue()
        ->and(Schema::hasTable('team_members'))->toBeTrue()
        ->and(Schema::hasTable('invites'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'latest_team_id'))->toBeTrue();
});

it('does not create the program tables or columns without programs', function () {
    expect(Schema::hasTable('programs'))->toBeFalse()
        ->and(Schema::hasColumn('users', 'latest_program_id'))->toBeFalse()
        ->and(Schema::hasColumn('invites', 'program_id'))->toBeFalse();
});
