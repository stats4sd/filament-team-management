<?php

namespace Stats4sd\FilamentTeamManagement\Contracts;

use Stats4sd\FilamentTeamManagement\Support\MembershipContext;

interface MembershipParticipant
{
    public function before(MembershipContext $context): void;

    public function after(MembershipContext $context): void;
}
