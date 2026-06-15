<?php

namespace App\Enums;

use App\Contracts\VitoEnum;

enum ErrorIssueStatus: string implements VitoEnum
{
    case UNRESOLVED = 'unresolved';
    case RESOLVED = 'resolved';
    case IGNORED = 'ignored';

    public function getColor(): string
    {
        return match ($this) {
            self::UNRESOLVED => 'danger',
            self::RESOLVED => 'success',
            self::IGNORED => 'gray',
        };
    }

    public function getText(): string
    {
        return $this->value;
    }
}
