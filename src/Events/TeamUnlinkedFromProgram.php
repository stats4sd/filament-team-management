<?php

namespace Stats4sd\FilamentTeamManagement\Events;

final class TeamUnlinkedFromProgram
{
    public function __construct(public readonly array $payload) {}
}
