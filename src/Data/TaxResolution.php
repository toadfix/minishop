<?php

namespace Minishop\Data;

use Minishop\Enums\TaxMode;

/**
 * Immutable DTO returned by ResolveTaxAction.
 *
 * Contains the resolved tax calculation result for a given address and subtotal.
 */
readonly class TaxResolution
{
    /**
     * @param  array<int, array{name: string, name_fr: ?string, rate: float, amount_cents: int}>  $breakdown
     */
    public function __construct(
        public TaxMode $mode,
        public ?string $zoneName,
        public array $breakdown,
        public int $totalTaxCents,
        public float $effectiveRate,
    ) {}

    /**
     * Serialise to array for JSON responses and order storage.
     *
     * @return array{mode: string, zone_name: ?string, breakdown: array<int, array{name: string, name_fr: ?string, rate: float, amount_cents: int}>, total_tax_cents: int, effective_rate: float}
     */
    public function toArray(): array
    {
        return [
            'mode' => $this->mode->value,
            'zone_name' => $this->zoneName,
            'breakdown' => $this->breakdown,
            'total_tax_cents' => $this->totalTaxCents,
            'effective_rate' => $this->effectiveRate,
        ];
    }
}
