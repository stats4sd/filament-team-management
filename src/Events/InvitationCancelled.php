<?php

namespace Stats4sd\FilamentTeamManagement\Events;

final class InvitationCancelled
{
    public function __construct(public readonly array $payload) {}
}
