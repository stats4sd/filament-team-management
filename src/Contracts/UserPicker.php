<?php

namespace Stats4sd\FilamentTeamManagement\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface UserPicker
{
    public function query(Authenticatable $actor, Model $target): Builder;
}
