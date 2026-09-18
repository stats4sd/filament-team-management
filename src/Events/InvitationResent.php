<?php

namespace Stats4sd\FilamentTeamManagement\Events;

final class InvitationResent
{
    public function __construct(public readonly array $payload) {}
}
