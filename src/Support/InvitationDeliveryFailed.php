<?php

namespace Stats4sd\FilamentTeamManagement\Support;

final class InvitationDeliveryFailed extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('The invitation was saved, but its email could not be dispatched. Please resend the invitation.');
    }
}
