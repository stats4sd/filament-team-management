<?php

namespace Stats4sd\FilamentTeamManagement\Events;

final class InvitationCreated
{
    public function __construct(public readonly array $payload) {}
}
