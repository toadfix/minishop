<?php

namespace Minishop\Payments;

use Illuminate\Support\Manager;
use Illuminate\Support\Str;
use Minishop\Payments\Gateways\NullGateway;
use Minishop\Payments\Gateways\StripeGateway;

class PaymentManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return config('minishop.default_payment_gateway', 'stripe');
    }

    /**
     * The selectable payment gateways (built-in plus any extended via
     * Payment::extend()), keyed by driver name with a human label.
     *
     * @return array<string, string>
     */
    public function availableGateways(): array
    {
        $gateways = [
            'stripe' => 'Stripe (online card payment)',
            'cod' => 'Cash on delivery (no online payment)',
        ];

        foreach (array_keys($this->customCreators) as $driver) {
            $gateways[$driver] ??= Str::headline($driver);
        }

        return $gateways;
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
