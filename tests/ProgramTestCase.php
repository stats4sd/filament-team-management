<?php

namespace Stats4sd\FilamentTeamManagement\Tests;

/**
 * Test case for program-mode tests. Setting $usePrograms = true runs the
 * program migrations (and adds the program columns to invites/users) up front,
 * and registers the Program panel — neither of which the runtime withPrograms()
 * helper can do after the fact.
 *
 * Bind it per-file with `uses(ProgramTestCase::class);` at the top of the test.
 */
class ProgramTestCase extends TestCase
{
    protected bool $usePrograms = true;
}
