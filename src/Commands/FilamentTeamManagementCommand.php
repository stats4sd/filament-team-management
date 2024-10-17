<?php

namespace Stats4sd\FilamentTeamManagement\Commands;

use Illuminate\Console\Command;

class FilamentTeamManagementCommand extends Command
{
    public $signature = 'filament-team-management';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
