<?php

namespace Minishop\Support;

class Money
{
    /**
     * Format an integer amount of minor units (cents) as a currency string.
     */
    public static function format(int $cents, ?string $symbol = null): string
    {
        $symbol ??= config('minishop.currency_symbol', '$');

        return $symbol.number_format($cents / 100, 2);
    }
}
