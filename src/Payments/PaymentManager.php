<?php

namespace Minishop\Payments;

use Illuminate\Support\Manager;
use Minishop\Payments\Gateways\NullGateway;
use Minishop\Payments\Gateways\StripeGateway;

class PaymentManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return config('minishop.default_payment_gateway', 'stripe');
    }

    protected function createStripeDriver(): StripeGateway
    {
        return new StripeGateway;
    }

    protected function createCodDriver(): NullGateway
    {
        return new NullGateway;
    }

    protected function createNullDriver(): NullGateway
    {
        return new NullGateway;
    }
}
