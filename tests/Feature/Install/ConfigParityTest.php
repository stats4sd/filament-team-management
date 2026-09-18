<?php

use Illuminate\Support\Facades\File;

/*
 * Locks down the whole 4.1 / 4.2 class of bugs permanently: the set of env keys
 * the install command writes into .env must exactly equal the set of env keys the
 * published config file reads. A mismatch in either direction means a value the
 * host configured via the installer is silently ignored (or a config key can never
 * be set), which is precisely how 4.1 (PROGRAM_MODEL vs PROGRAMS_FOREIGN_KEY) and
 * 4.2 (USERS_* vs USER_*) slipped through.
 */

function extractConfigEnvKeys(): array
{
    $packageRoot = dirname(__DIR__, 3);
    $source = File::get($packageRoot . '/config/filament-team-management.php');

    preg_match_all("/env\\('(FILAMENT_TEAM_MANAGEMENT_[A-Z_]+)'/", $source, $matches);

    return collect($matches[1])->unique()->sort()->values()->all();
}

function extractInstallerEnvKeys(): array
{
    $source = File::get(dirname(__DIR__, 3) . '/src/Commands/InstallFilamentTeamManagement.php');
    preg_match_all("/'(FILAMENT_TEAM_MANAGEMENT_[A-Z_]+)'/", $source, $full);
    preg_match_all("/'([A-Z_]+)' => '(?:models|table_names|column_names)\\.[^']+'/", $source, $suffix);

    return collect(array_merge($full[1], array_map(fn ($key) => 'FILAMENT_TEAM_MANAGEMENT_' . $key, $suffix[1])))->unique()->sort()->values()->all();
}

it('reads and writes the same set of env keys across config and installer', function () {
    $configReads = extractConfigEnvKeys();
    $installerWrites = extractInstallerEnvKeys();

    // sanity: neither extraction returned nothing (guards against a broken regex)
    expect($configReads)->not->toBeEmpty()
        ->and($installerWrites)->not->toBeEmpty();

    $readButNeverWritten = array_values(array_diff($configReads, $installerWrites));
    $writtenButNeverRead = array_values(array_diff($installerWrites, $configReads));

    expect($readButNeverWritten)->toBe(
        [],
        'Config reads env keys the installer never writes (host cannot configure them): ' . implode(', ', $readButNeverWritten)
    );

    expect($writtenButNeverRead)->toBe(
        [],
        'Installer writes env keys the config never reads (silently ignored): ' . implode(', ', $writtenButNeverRead)
    );
});
