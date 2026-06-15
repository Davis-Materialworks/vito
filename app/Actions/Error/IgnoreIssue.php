<?php

namespace App\Actions\Error;

use App\Enums\ErrorIssueStatus;
use App\Models\ErrorIssue;

class IgnoreIssue
{
    public function ignore(ErrorIssue $issue): ErrorIssue
    {
        $issue->status = ErrorIssueStatus::IGNORED;
        $issue->save();

        return $issue;
    }
}
