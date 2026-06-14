<?php

namespace Minishop\Payments\Facades;

use Illuminate\Support\Facades\Facade;
use Minishop\Payments\PaymentManager;

/**
 * @method static \Minishop\Payments\Contracts\PaymentGatewayContract driver(string $driver = null)
 * @method static void extend(string $driver, \Closure $callback)
 * @method static array<string, string> availableGateways()
 *
 * @see PaymentManager
 */
class Payment extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'minishop.payment';
    }
}
