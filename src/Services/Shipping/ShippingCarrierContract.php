<?php

namespace Minishop\Services\Shipping;

use Illuminate\Support\Collection;

interface ShippingCarrierContract
{
    public function driverKey(): string;

    /**
     * @return Collection<int, ShippingRateData>
     */
    public function getRates(ShipmentData $shipment): Collection;
}
