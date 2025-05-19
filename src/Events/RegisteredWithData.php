<?php

namespace Stats4sd\FilamentTeamManagement\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Queue\SerializesModels;

class RegisteredWithData
{
    use SerializesModels;

    public function __construct(public Authenticatable $user, public array $data) {}
}
