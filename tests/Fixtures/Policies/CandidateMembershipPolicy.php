<?php

namespace Stats4sd\FilamentTeamManagement\Tests\Fixtures\Policies;

use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\HostUser;

class CandidateMembershipPolicy extends MembershipPolicy
{
    public array $denied = [];

    public array $attempted = [];

    public array $transactionMembershipCounts = [];

    public bool $canViewMembers = true;

    public function viewMembers(HostUser $actor, Model $target): bool
    {
        return $this->canViewMembers && parent::viewMembers($actor, $target);
    }

    public function addMember(HostUser $actor, Model $target, Model $member): bool
    {
        $this->attempted[] = $member->getKey();
        if ($target->getConnection()->transactionLevel() > 0) {
            $this->transactionMembershipCounts[$member->getKey()] = $target->users()->count();
        }

        return ! in_array($member->getKey(), $this->denied, true) && parent::addMember($actor, $target, $member);
    }
}
