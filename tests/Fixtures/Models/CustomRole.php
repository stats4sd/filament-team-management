<?php

namespace Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models;

use Spatie\Permission\Models\Role;

/**
 * A custom Role subclass used to prove relationships resolve their related
 * model through config rather than a hardcoded class.
 */
class CustomRole extends Role {}
