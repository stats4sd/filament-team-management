<?php

namespace Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models;

use Stats4sd\FilamentTeamManagement\Models\User;

/**
 * A custom User subclass used to prove relationships resolve their related
 * model through config rather than a hardcoded class.
 */
class CustomUser extends User {}
