<?php

namespace Stats4sd\FilamentTeamManagement\Tests;

/**
 * Test case for program-mode tests. Setting $usePrograms = true runs the
 * program migrations up front and registers the Program panel; the runtime
 * withPrograms() helper can do the former but not the latter.
 *
 * Bind it per-file with `uses(ProgramTestCase::class);` at the top of the test.
 */
class ProgramTestCase extends TestCase
{
    protected bool $usePrograms = true;
}
