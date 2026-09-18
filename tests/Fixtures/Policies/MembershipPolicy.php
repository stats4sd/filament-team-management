<?php

namespace Stats4sd\FilamentTeamManagement\Tests\Fixtures\Policies;

use Illuminate\Database\Eloquent\Model;
use Stats4sd\FilamentTeamManagement\Tests\Fixtures\Models\HostUser;

class MembershipPolicy
{
    public function viewAny(HostUser $actor): bool
    {
        return (bool) $actor->host_admin;
    }

    public function create(HostUser $actor): bool
    {
        return (bool) $actor->host_admin;
    }

    public function view(HostUser $actor, Model $target): bool
    {
        return $actor->allowed($target, 'view') || $target->users()->whereKey($actor->getKey())->exists();
    }

    public function update(HostUser $actor, Model $target): bool
    {
        return $actor->allowed($target, 'update');
    }

    public function delete(HostUser $actor, Model $target): bool
    {
        return $actor->allowed($target, 'delete');
    }

    public function deleteAny(HostUser $actor): bool
    {
        return (bool) $actor->host_admin;
    }

    public function viewMembers(HostUser $actor, Model $target): bool
    {
        return $this->view($actor, $target);
    }

    public function viewInvitations(HostUser $actor, Model $target): bool
    {
        return $actor->allowed($target, 'viewInvitations');
    }

    public function inviteMember(HostUser $actor, Model $target): bool
    {
        return $actor->allowed($target, 'inviteMember');
    }

    public function addMember(HostUser $actor, Model $target, Model $member): bool
    {
        return $actor->allowed($target, 'addMember');
    }

    public function removeMember(HostUser $actor, Model $target, Model $member): bool
    {
        return $actor->allowed($target, 'removeMember');
    }

    public function leave(HostUser $actor, Model $target): bool
    {
        return $target->users()->whereKey($actor->getKey())->exists();
    }

    public function resendInvitation(HostUser $actor, Model $target, Model $invite): bool
    {
        return $actor->allowed($target, 'resendInvitation');
    }

    public function cancelInvitation(HostUser $actor, Model $target, Model $invite): bool
    {
        return $actor->allowed($target, 'cancelInvitation');
    }

    public function viewTeams(HostUser $actor, Model $target): bool
    {
        return $this->view($actor, $target);
    }

    public function linkTeam(HostUser $actor, Model $target, Model $team): bool
    {
        return $actor->allowed($target, 'linkTeam');
    }

    public function unlinkTeam(HostUser $actor, Model $target, Model $team): bool
    {
        return $actor->allowed($target, 'unlinkTeam');
    }

    public function linkProgram(HostUser $actor, Model $target, Model $program): bool
    {
        return $actor->allowed($target, 'linkProgram');
    }

    public function unlinkProgram(HostUser $actor, Model $target, Model $program): bool
    {
        return $actor->allowed($target, 'unlinkProgram');
    }
}
