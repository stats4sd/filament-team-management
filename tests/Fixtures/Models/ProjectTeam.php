<?php

namespace Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models;

use Stats4sd\FilamentTeamManagement\Models\Team;

/**
 * A multi-word custom Team subclass, used to prove getModelNameLower() and the
 * config-indirected relationships survive a host app repointing the model.
 */
class ProjectTeam extends Team {}
