<?php

namespace Minishop\Tests\Unit\DTOs;

use Minishop\Data\TaxResolution;
use Minishop\Enums\TaxMode;
use Minishop\Tests\TestCase;

class TaxResolutionTest extends TestCase
{
    public function test_total_is_sum_of_all_line_amounts(): void
    {
        $resolution = new TaxResolution(
            mode: TaxMode::ZoneBased,
            zoneName: 'Quebec',
            breakdown: [
                ['name' => 'GST', 'name_fr' => 'TPS', 'rate' => 5.0, 'amount_cents' => 500],
                ['name' => 'QST', 'name_fr' => 'TVQ', 'rate' => 9.975, 'amount_cents' => 1047],
            ],
            totalTaxCents: 1547,
            effectiveRate: 0.1547,
        );

        $this->assertEquals(1547, $resolution->totalTaxCents);
        $sumFromBreakdown = array_sum(array_column($resolution->breakdown, 'amount_cents'));
        $this->assertEquals($resolution->totalTaxCents, $sumFromBreakdown);
    }

    public function test_to_array_contains_required_keys(): void
    {
        $resolution = new TaxResolution(
            mode: TaxMode::ZoneBased,
            zoneName: 'Ontario',
            breakdown: [
                ['name' => 'HST', 'name_fr' => null, 'rate' => 13.0, 'amount_cents' => 1300],
            ],
            totalTaxCents: 1300,
            effectiveRate: 0.13,
        );

        $array = $resolution->toArray();

        $this->assertArrayHasKey('mode', $array);
        $this->assertArrayHasKey('zone_name', $array);
        $this->assertArrayHasKey('breakdown', $array);
        $this->assertArrayHasKey('total_tax_cents', $array);
        $this->assertArrayHasKey('effective_rate', $array);
        $this->assertEquals('zone_based', $array['mode']);
        $this->assertEquals('Ontario', $array['zone_name']);
    }

    public function test_empty_rates_yields_zero_total(): void
    {
        $resolution = new TaxResolution(
            mode: TaxMode::ZoneBased,
            zoneName: null,
            breakdown: [],
            totalTaxCents: 0,
            effectiveRate: 0.0,
        );

        $this->assertEquals(0, $resolution->totalTaxCents);
        $this->assertEmpty($resolution->breakdown);
        $this->assertNull($resolution->zoneName);
    }
}
