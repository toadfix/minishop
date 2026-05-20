<?php

namespace Minishop\Tests\Feature\Storefront;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Minishop\Models\StoreSettings;
use Minishop\Models\TaxZone;
use Minishop\Models\TaxZoneRate;
use Minishop\Tests\TestCase;

class TaxPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        StoreSettings::query()->updateOrCreate([], ['tax_mode' => 'zone_based', 'tax_rate' => 0]);
        Cache::flush();
    }

    public function test_ontario_returns_hst_breakdown(): void
    {
        $zone = TaxZone::factory()->ontario()->create();
        TaxZoneRate::factory()->hst()->for($zone, 'zone')->create();

        $this->postJson(route('storefront.checkout.tax-preview'), [
            'country' => 'CA',
            'province_code' => 'ON',
            'subtotal' => 10000,
        ])->assertOk()
            ->assertJsonPath('tax.zone_name', 'Ontario')
            ->assertJsonPath('tax.total_tax_cents', 1300)
            ->assertJsonPath('tax.breakdown.0.name', 'HST');
    }

    public function test_quebec_returns_gst_and_compound_qst_breakdown(): void
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

        $this->postJson(route('storefront.checkout.tax-preview'), [
            'country' => 'CA',
            'province_code' => 'QC',
            'subtotal' => 10000,
        ])->assertOk()
            ->assertJsonPath('tax.total_tax_cents', 1547)
            ->assertJsonPath('tax.breakdown.0.name', 'GST')
            ->assertJsonPath('tax.breakdown.1.name', 'QST');
    }

    public function test_bc_returns_gst_and_pst_breakdown(): void
    {
        $zone = TaxZone::factory()->create([
            'name' => 'British Columbia',
            'country_code' => 'CA',
            'province_code' => 'BC',
            'is_active' => true,
            'priority' => 10,
        ]);
        TaxZoneRate::factory()->for($zone, 'zone')->create(['name' => 'GST', 'rate' => 5.0, 'sort_order' => 1]);
        TaxZoneRate::factory()->for($zone, 'zone')->create(['name' => 'PST', 'rate' => 7.0, 'sort_order' => 2]);

        $this->postJson(route('storefront.checkout.tax-preview'), [
            'country' => 'CA',
            'province_code' => 'BC',
            'subtotal' => 10000,
        ])->assertOk()
            ->assertJsonPath('tax.total_tax_cents', 1200)
            ->assertJsonPath('tax.breakdown.0.name', 'GST')
            ->assertJsonPath('tax.breakdown.1.name', 'PST');
    }

    public function test_international_address_returns_zero_tax(): void
    {
        $globalZone = TaxZone::factory()->wildcard()->create();
        TaxZoneRate::factory()->for($globalZone, 'zone')->create(['name' => 'VAT', 'rate' => 0.0, 'sort_order' => 1]);

        $this->postJson(route('storefront.checkout.tax-preview'), [
            'country' => 'DE',
            'province_code' => null,
            'subtotal' => 10000,
        ])->assertOk()
            ->assertJsonPath('tax.total_tax_cents', 0);
    }

    public function test_flat_rate_mode_bypasses_zone_lookup(): void
    {
        StoreSettings::query()->updateOrCreate([], ['tax_mode' => 'flat_rate', 'tax_rate' => 13.0]);

        // Create a zone that would match — should be ignored in flat_rate mode
        $zone = TaxZone::factory()->ontario()->create();
        TaxZoneRate::factory()->for($zone, 'zone')->create(['name' => 'ZONE_RATE', 'rate' => 99.0]);

        $this->postJson(route('storefront.checkout.tax-preview'), [
            'country' => 'CA',
            'province_code' => 'ON',
            'subtotal' => 10000,
        ])->assertOk()
            ->assertJsonPath('tax.mode', 'flat_rate')
            ->assertJsonPath('tax.total_tax_cents', 1300);
    }

    public function test_unknown_province_falls_back_to_canada_gst(): void
    {
        $catchAll = TaxZone::factory()->create([
            'name' => 'Canada',
            'country_code' => 'CA',
            'province_code' => null,
            'is_active' => true,
            'priority' => 0,
        ]);
        TaxZoneRate::factory()->gst()->for($catchAll, 'zone')->create();

        $this->postJson(route('storefront.checkout.tax-preview'), [
            'country' => 'CA',
            'province_code' => 'NT',
            'subtotal' => 10000,
        ])->assertOk()
            ->assertJsonPath('tax.zone_name', 'Canada')
            ->assertJsonPath('tax.total_tax_cents', 500);
    }

    public function test_unknown_country_falls_back_to_global_zero_rate(): void
    {
        $globalZone = TaxZone::factory()->wildcard()->create(['name' => 'International']);
        TaxZoneRate::factory()->for($globalZone, 'zone')->create(['name' => 'None', 'rate' => 0.0]);

        $this->postJson(route('storefront.checkout.tax-preview'), [
            'country' => 'XX',
            'subtotal' => 10000,
        ])->assertOk()
            ->assertJsonPath('tax.zone_name', 'International')
            ->assertJsonPath('tax.total_tax_cents', 0);
    }

    public function test_validation_fails_without_required_fields(): void
    {
        $this->postJson(route('storefront.checkout.tax-preview'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['country', 'subtotal']);
    }

    public function test_subtotal_must_be_integer(): void
    {
        $this->postJson(route('storefront.checkout.tax-preview'), [
            'country' => 'CA',
            'subtotal' => 'not-an-integer',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['subtotal']);
    }

    public function test_returns_429_after_rate_limit_exceeded(): void
    {
        // Hit the endpoint 31 times to exceed the throttle:30,1 limit
        for ($i = 0; $i < 30; $i++) {
            $this->postJson(route('storefront.checkout.tax-preview'), [
                'country' => 'CA',
                'subtotal' => 1000,
            ]);
        }

        $this->postJson(route('storefront.checkout.tax-preview'), [
            'country' => 'CA',
            'subtotal' => 1000,
        ])->assertStatus(429);
    }
}
