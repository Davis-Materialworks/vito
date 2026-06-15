<?php

namespace App\Actions\Error;

use App\Enums\ErrorIssueStatus;
use App\Models\ErrorIssue;

class ResolveIssue
{
    public function resolve(ErrorIssue $issue): ErrorIssue
    {
        $issue->status = ErrorIssueStatus::RESOLVED;
        $issue->resolved_at = now();
        $issue->save();

        return $issue;
    }
}
