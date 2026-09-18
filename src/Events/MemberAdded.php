<?php

namespace Stats4sd\FilamentTeamManagement\Events;

final class MemberAdded
{
    public function __construct(public readonly array $payload) {}
}
