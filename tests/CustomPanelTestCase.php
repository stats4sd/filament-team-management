<?php

namespace Stats4sd\FilamentTeamManagement\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CustomPanelTestCase extends ProgramTestCase
{
    protected array $panelIds = ['app' => 'workspace', 'program' => 'initiatives', 'admin' => 'control'];

    protected array $panelPaths = ['app' => 'portal', 'program' => 'program-office', 'admin' => 'backoffice'];

    protected ?string $tenantSlugAttribute = 'slug';

    protected bool $registration = true;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::table('teams', fn (Blueprint $table) => $table->string('slug')->nullable()->unique());
        Schema::table('programs', fn (Blueprint $table) => $table->string('slug')->nullable()->unique());
    }
}
