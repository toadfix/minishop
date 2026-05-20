<?php

namespace Minishop\Enums;

enum ReturnStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Received = 'received';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Requested',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Received => 'Received',
            self::Refunded => 'Refunded',
        };
    }

    /** @return string[] */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Requested => [self::Approved->value, self::Rejected->value],
            self::Approved => [self::Received->value],
            self::Received => [self::Refunded->value],
            self::Rejected, self::Refunded => [],
        };
    }
}
