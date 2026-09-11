<?php

use Illuminate\Support\ServiceProvider;
use Stats4sd\FilamentTeamManagement\FilamentTeamManagementServiceProvider;

/**
 * @return array<int, string> Published migration basenames for a publish tag, in filesystem sort order.
 */
function publishedMigrationNames(string $tag): array
{
    $paths = ServiceProvider::pathsToPublish(FilamentTeamManagementServiceProvider::class, "filament-team-management-migrations-{$tag}");

    return collect($paths)->values()->map(fn (string $path) => basename($path))->sort()->values()->all();
}

// The provider shares one clock across both tags. Without that, both tags start from "now" and
// the published files interleave by name, so 10_add_program_foreign_keys could run before stubs
// 3 and 9 have created the columns it constrains.
it('publishes every program migration with a timestamp after every default migration', function () {
    $default = publishedMigrationNames('default');
    $program = publishedMigrationNames('program');

    expect($default)->toHaveCount(4)
        ->and($program)->toHaveCount(4)
        ->and(end($program))->toEndWith('10_add_program_foreign_keys.php')
        ->and(strcmp((string) reset($program), (string) end($default)))->toBeGreaterThan(0);
});

it('publishes the program foreign-key migration under the program tag only', function () {
    expect(implode(' ', publishedMigrationNames('default')))->not->toContain('add_program_foreign_keys')
        ->and(implode(' ', publishedMigrationNames('program')))->toContain('add_program_foreign_keys');
});
