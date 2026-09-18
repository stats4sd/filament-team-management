<?php

namespace Stats4sd\FilamentTeamManagement\Events;

final class MemberRemoved
{
    public function __construct(public readonly array $payload) {}
}
