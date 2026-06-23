<?php

use Illuminate\Support\Facades\Schema;

it('creates the program tables and the program columns in program mode', function () {
    expect(Schema::hasTable('programs'))->toBeTrue()
        ->and(Schema::hasTable('program_members'))->toBeTrue()
        ->and(Schema::hasTable('program_team'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'latest_program_id'))->toBeTrue()
        ->and(Schema::hasColumn('invites', 'program_id'))->toBeTrue();
});
