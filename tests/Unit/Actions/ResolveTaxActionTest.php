<?php

namespace Minishop\Tests\Unit\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Minishop\Actions\ResolveTaxAction;
use Minishop\Data\TaxResolution;
use Minishop\Enums\TaxMode;
use Minishop\Models\StoreSettings;
use Minishop\Models\TaxZone;
use Minishop\Models\TaxZoneRate;
use Minishop\Tests\TestCase;

class ResolveTaxActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure zone_based mode by default; individual tests override when needed
        StoreSettings::query()->updateOrCreate([], ['tax_mode' => 'zone_based', 'tax_rate' => 0]);
        Cache::flush();
    }

    public function test_ontario_hst_calculates_to_1300_cents_on_10000(): void
    {
        $zone = TaxZone::factory()->ontario()->create();
        TaxZoneRate::factory()->hst()->for($zone, 'zone')->create();

        $action = app(ResolveTaxAction::class);
        $result = $action->execute('CA', 'ON', 10000);

        $this->assertInstanceOf(TaxResolution::class, $result);
        $this->assertEquals(1300, $result->totalTaxCents);
        $this->assertCount(1, $result->breakdown);
        $this->assertEquals('HST', $result->breakdown[0]['name']);
        $this->assertEquals(1300, $result->breakdown[0]['amount_cents']);
        $this->assertEquals('Ontario', $result->zoneName);
    }

    public function test_quebec_gst_plus_compound_qst_calculates_to_1547_cents_on_10000(): void
    {
        $zone = TaxZone::factory()->quebec()->create();
        TaxZoneRate::factory()->gst()->for($zone, 'zone')->create(['sort_order' => 1]);
        TaxZoneRate::factory()->for($zone, 'zone')->create([
            'name' => 'QST',
            'name_fr' => 'TVQ',
            'rate' => 9.9750,
            'is_compound' => true,
            'sort_order' => 2,
        ]);

        $action = app(ResolveTaxAction::class);
        $result = $action->execute('CA', 'QC', 10000);

        // GST: 10000 * 5/100 = 500
        // QST: (10000 + 500) * 9.975/100 = 10500 * 9.975/100 = 1047.375 -> 1047 cents
        $this->assertEquals(1547, $result->totalTaxCents);
        $this->assertCount(2, $result->breakdown);
        $this->assertEquals('GST', $result->breakdown[0]['name']);
        $this->assertEquals(500, $result->breakdown[0]['amount_cents']);
        $this->assertEquals('QST', $result->breakdown[1]['name']);
        $this->assertEquals(1047, $result->breakdown[1]['amount_cents']);
    }

    public function test_compound_rate_uses_prior_simple_tax_as_additional_base(): void
    {
        $zone = TaxZone::factory()->create([
            'name' => 'BC',
            'country_code' => 'CA',
            'province_code' => 'BC',
            'is_active' => true,
            'priority' => 10,
        ]);
        TaxZoneRate::factory()->for($zone, 'zone')->create([
            'name' => 'GST',
            'rate' => 5.0,
            'is_compound' => false,
            'sort_order' => 1,
        ]);
        TaxZoneRate::factory()->for($zone, 'zone')->create([
            'name' => 'COMPOUND',
            'rate' => 10.0,
            'is_compound' => true,
            'sort_order' => 2,
        ]);

        $action = app(ResolveTaxAction::class);
        $result = $action->execute('CA', 'BC', 10000);

        // GST simple: 10000 * 5/100 = 500
        // Compound: (10000 + 500) * 10/100 = 1050
        $this->assertEquals(500, $result->breakdown[0]['amount_cents']);
        $this->assertEquals(1050, $result->breakdown[1]['amount_cents']);
        $this->assertEquals(1550, $result->totalTaxCents);
    }

    public function test_non_compound_rate_uses_original_subtotal_only(): void
    {
        $zone = TaxZone::factory()->create([
            'name' => 'Alberta',
            'country_code' => 'CA',
            'province_code' => 'AB',
            'is_active' => true,
            'priority' => 10,
        ]);
        TaxZoneRate::factory()->for($zone, 'zone')->create([
            'name' => 'GST',
            'rate' => 5.0,
            'is_compound' => false,
            'sort_order' => 1,
        ]);
        TaxZoneRate::factory()->for($zone, 'zone')->create([
            'name' => 'PST',
            'rate' => 7.0,
            'is_compound' => false,
            'sort_order' => 2,
        ]);

        $action = app(ResolveTaxAction::class);
        $result = $action->execute('CA', 'AB', 10000);

        // Both non-compound: each uses base 10000
        $this->assertEquals(500, $result->breakdown[0]['amount_cents']);
        $this->assertEquals(700, $result->breakdown[1]['amount_cents']);
        $this->assertEquals(1200, $result->totalTaxCents);
    }

    public function test_resolves_province_specific_zone_over_country_catch_all(): void
    {
        // Country catch-all
        $countryZone = TaxZone::factory()->create([
            'name' => 'Canada GST',
            'country_code' => 'CA',
            'province_code' => null,
            'is_active' => true,
            'priority' => 0,
        ]);
        TaxZoneRate::factory()->for($countryZone, 'zone')->create(['name' => 'GST', 'rate' => 5.0, 'sort_order' => 1]);

        // Province-specific zone with higher priority
        $provinceZone = TaxZone::factory()->ontario()->create();
        TaxZoneRate::factory()->hst()->for($provinceZone, 'zone')->create();

        $action = app(ResolveTaxAction::class);
        $result = $action->execute('CA', 'ON', 10000);

        $this->assertEquals('Ontario', $result->zoneName);
        $this->assertEquals(1300, $result->totalTaxCents);
    }

    public function test_falls_back_to_country_catch_all_when_no_province_match(): void
    {
        $countryZone = TaxZone::factory()->create([
            'name' => 'Canada GST',
            'country_code' => 'CA',
            'province_code' => null,
            'is_active' => true,
            'priority' => 0,
        ]);
        TaxZoneRate::factory()->for($countryZone, 'zone')->create(['name' => 'GST', 'rate' => 5.0, 'sort_order' => 1]);

        $action = app(ResolveTaxAction::class);
        $result = $action->execute('CA', 'YT', 10000);

        $this->assertEquals('Canada GST', $result->zoneName);
        $this->assertEquals(500, $result->totalTaxCents);
    }

    public function test_falls_back_to_global_null_zone_when_no_country_match(): void
    {
        $globalZone = TaxZone::factory()->wildcard()->create(['name' => 'International']);
        TaxZoneRate::factory()->for($globalZone, 'zone')->create(['name' => 'VAT', 'rate' => 0.0, 'sort_order' => 1]);

        $action = app(ResolveTaxAction::class);
        $result = $action->execute('FR', null, 10000);

        $this->assertEquals('International', $result->zoneName);
        $this->assertEquals(0, $result->totalTaxCents);
    }

    public function test_returns_zero_tax_when_no_zone_matches(): void
    {
        $action = app(ResolveTaxAction::class);
        $result = $action->execute('XX', 'ZZ', 10000);

        $this->assertEquals(0, $result->totalTaxCents);
        $this->assertNull($result->zoneName);
        $this->assertEmpty($result->breakdown);
    }

    public function test_inactive_zone_is_not_resolved(): void
    {
        $zone = TaxZone::factory()->ontario()->inactive()->create();
        TaxZoneRate::factory()->hst()->for($zone, 'zone')->create();

        $action = app(ResolveTaxAction::class);
        $result = $action->execute('CA', 'ON', 10000);

        $this->assertEquals(0, $result->totalTaxCents);
        $this->assertNull($result->zoneName);
    }

    public function test_higher_priority_zone_wins_over_lower_priority(): void
    {
        $lowPriority = TaxZone::factory()->create([
            'name' => 'Low Priority',
            'country_code' => 'CA',
            'province_code' => 'ON',
            'is_active' => true,
            'priority' => 0,
        ]);
        TaxZoneRate::factory()->for($lowPriority, 'zone')->create(['name' => 'GST', 'rate' => 5.0, 'sort_order' => 1]);

        $highPriority = TaxZone::factory()->create([
            'name' => 'High Priority',
            'country_code' => 'CA',
            'province_code' => 'ON',
            'is_active' => true,
            'priority' => 20,
        ]);
        TaxZoneRate::factory()->hst()->for($highPriority, 'zone')->create();

        $action = app(ResolveTaxAction::class);
        $result = $action->execute('CA', 'ON', 10000);

        $this->assertEquals('High Priority', $result->zoneName);
        $this->assertEquals(1300, $result->totalTaxCents);
    }

    public function test_flat_rate_mode_delegates_to_store_settings_and_ignores_zones(): void
    {
        StoreSettings::query()->updateOrCreate([], ['tax_mode' => 'flat_rate', 'tax_rate' => 13.0]);

        // Create a zone-based zone — should be ignored in flat_rate mode
        $zone = TaxZone::factory()->ontario()->create();
        TaxZoneRate::factory()->for($zone, 'zone')->create(['name' => 'HIGH', 'rate' => 99.0, 'sort_order' => 1]);

        $action = app(ResolveTaxAction::class);
        $result = $action->execute('CA', 'ON', 10000);

        $this->assertEquals(TaxMode::FlatRate, $result->mode);
        // 13% flat rate on 10000 = 1300
        $this->assertEquals(1300, $result->totalTaxCents);
        $this->assertNull($result->zoneName);
    }
}
