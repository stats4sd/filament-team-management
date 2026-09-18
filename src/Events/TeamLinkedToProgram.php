<?php

namespace Stats4sd\FilamentTeamManagement\Events;

final class TeamLinkedToProgram
{
    public function __construct(public readonly array $payload) {}
}
