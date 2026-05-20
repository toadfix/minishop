<?php

namespace Minishop\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Processing => 'Processing',
            self::Shipped => 'Shipped',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
            self::Refunded => 'Refunded',
        };
    }

    /**
     * @return array<string, string[]>
     */
    public static function transitions(): array
    {
        return [
            self::Pending->value => [self::Processing->value, self::Cancelled->value],
            self::Processing->value => [self::Shipped->value, self::Cancelled->value],
            self::Shipped->value => [self::Delivered->value, self::Cancelled->value],
            self::Delivered->value => [self::Refunded->value],
            self::Cancelled->value => [],
            self::Refunded->value => [],
        ];
    }
}
