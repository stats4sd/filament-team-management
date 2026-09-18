<?php

namespace Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models;

class StringKeyCandidateUser extends HostUser
{
    protected $primaryKey = 'person_key';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;
}
