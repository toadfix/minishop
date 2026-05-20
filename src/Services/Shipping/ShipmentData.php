<?php

namespace Minishop\Services\Shipping;

final readonly class ShipmentData
{
    public function __construct(
        public string $originPostcode,
        public string $destinationPostcode,
        public string $destinationCountry,
        public int $weightGrams,
    ) {}

    public function weightKg(): string
    {
        return number_format($this->weightGrams / 1000, 3);
    }

    public function cacheKey(string $carrier): string
    {
        // Round weight to nearest 100g to improve cache hit rate without
        // meaningfully affecting Canada Post rate accuracy
        $roundedWeight = (int) round($this->weightGrams / 100) * 100;

        return sprintf(
            'shipping_rates.%s.%s.%s.%s.%d',
            $carrier,
            $this->destinationCountry,
            strtoupper(preg_replace('/\s+/', '', $this->originPostcode)),
            strtoupper(preg_replace('/\s+/', '', $this->destinationPostcode)),
            $roundedWeight,
        );
    }
}
