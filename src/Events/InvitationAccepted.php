<?php

namespace Stats4sd\FilamentTeamManagement\Events;

final class InvitationAccepted
{
    public function __construct(public readonly array $payload) {}
}
