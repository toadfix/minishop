<?php

namespace Minishop\Services\Shipping;

final readonly class ShippingRateData
{
    public function __construct(
        public string $carrier,
        public string $serviceCode,
        public string $serviceName,
        public int $amountCents,
        public ?string $expectedDelivery,
        public ?int $shippingMethodId,
    ) {}
}
