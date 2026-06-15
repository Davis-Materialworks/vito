<?php

namespace App\Policies;

use App\Models\ErrorIssue;
use App\Models\Server;
use App\Models\Site;
use App\Models\User;
use App\Traits\HasRolePolicies;
use Illuminate\Auth\Access\HandlesAuthorization;

class ErrorIssuePolicy
{
    use HandlesAuthorization;
    use HasRolePolicies;

    public function viewAny(User $user, Server $server): bool
    {
        return $this->hasReadAccess($user, $server->project) && $server->isReady();
    }

    public function view(User $user, ErrorIssue $errorIssue, Server $server): bool
    {
        return $this->hasReadAccess($user, $errorIssue->server->project)
            && $errorIssue->server_id === $server->id;
    }

    public function update(User $user, ErrorIssue $errorIssue, Server $server): bool
    {
        return $this->hasWriteAccess($user, $errorIssue->server->project)
            && $errorIssue->server_id === $server->id;
    }
}
