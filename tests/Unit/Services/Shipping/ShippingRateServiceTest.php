<?php

namespace Minishop\Tests\Unit\Services\Shipping;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Minishop\Models\Product;
use Minishop\Models\ProductVariant;
use Minishop\Models\ShippingMethod;
use Minishop\Services\Shipping\ShipmentData;
use Minishop\Services\Shipping\ShippingCarrierContract;
use Minishop\Services\Shipping\ShippingRateData;
use Minishop\Services\Shipping\ShippingRateService;
use Minishop\Tests\TestCase;

class ShippingRateServiceTest extends TestCase
{
    use RefreshDatabase;

    private ShippingRateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ShippingRateService;
    }

    public function test_returns_empty_collection_for_unregistered_carrier(): void
    {
        $methods = collect([
            (object) ['carrier' => 'canada_post', 'service_code' => 'DOM.EP'],
        ]);

        $shipment = new ShipmentData('K1A 0A6', 'V6B 2W9', 'CA', 1000);

        $rates = $this->service->fetchRates($methods, $shipment);

        $this->assertTrue($rates->isEmpty());
    }

    public function test_matches_rate_to_shipping_method_by_service_code(): void
    {
        $method = ShippingMethod::factory()->calculated()->create([
            'service_code' => 'DOM.EP',
        ]);

        $fakeDriver = $this->fakeDriver('canada_post', [
            new ShippingRateData('canada_post', 'DOM.EP', 'Expedited Parcel', 1250, null, null),
        ]);

        $this->service->registerDriver($fakeDriver);

        $rates = $this->service->fetchRates(collect([$method]), new ShipmentData('K1A 0A6', 'V6B 2W9', 'CA', 500));

        $this->assertCount(1, $rates);
        $this->assertSame($method->id, $rates->first()->shippingMethodId);
    }

    public function test_calculates_total_weight_correctly(): void
    {
        $product = Product::factory()->create(['weight_grams' => 500]);

        $items = [
            ['product_id' => $product->id, 'variant_id' => null, 'quantity' => 3],
        ];

        $weight = $this->service->calculateTotalWeight($items);

        $this->assertSame(1500, $weight);
    }

    public function test_falls_back_to_500g_per_item_when_weights_not_set(): void
    {
        $product = Product::factory()->create(['weight_grams' => null]);

        $items = [
            ['product_id' => $product->id, 'variant_id' => null, 'quantity' => 2],
        ];

        $weight = $this->service->calculateTotalWeight($items);

        $this->assertSame(1000, $weight); // 2 × 500g fallback
    }

    public function test_uses_variant_weight_over_product_weight(): void
    {
        $product = Product::factory()->create(['weight_grams' => 1000]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'weight_grams' => 300,
        ]);

        $items = [
            ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 1],
        ];

        $weight = $this->service->calculateTotalWeight($items);

        $this->assertSame(300, $weight);
    }

    public function test_batch_loads_products_and_variants_without_n_plus_one(): void
    {
        $product1 = Product::factory()->create(['weight_grams' => 200]);
        $product2 = Product::factory()->create(['weight_grams' => 400]);

        $items = [
            ['product_id' => $product1->id, 'variant_id' => null, 'quantity' => 1],
            ['product_id' => $product2->id, 'variant_id' => null, 'quantity' => 2],
        ];

        $queryCount = 0;
        \DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        $this->service->calculateTotalWeight($items);

        // Should make at most 2 queries: one for products, one for variants (variants is empty)
        $this->assertLessThanOrEqual(2, $queryCount);
    }

    public function test_cache_key_includes_destination_country(): void
    {
        $shipment = new ShipmentData('K1A 0A6', 'V6B 2W9', 'CA', 1000);
        $shipmentUS = new ShipmentData('K1A 0A6', 'V6B 2W9', 'US', 1000);

        $this->assertNotSame(
            $shipment->cacheKey('canada_post'),
            $shipmentUS->cacheKey('canada_post'),
        );
    }

    public function test_cache_key_normalises_postcode_whitespace(): void
    {
        $with = new ShipmentData('K1A 0A6', 'V6B 2W9', 'CA', 1000);
        $without = new ShipmentData('K1A0A6', 'V6B2W9', 'CA', 1000);

        $this->assertSame(
            $with->cacheKey('canada_post'),
            $without->cacheKey('canada_post'),
        );
    }

    public function test_weight_is_rounded_to_nearest_100g_in_cache_key(): void
    {
        $a = new ShipmentData('K1A 0A6', 'V6B 2W9', 'CA', 1040);
        $b = new ShipmentData('K1A 0A6', 'V6B 2W9', 'CA', 1060);

        // Both round to 1000 and 1100 respectively
        $this->assertStringEndsWith('.1000', $a->cacheKey('canada_post'));
        $this->assertStringEndsWith('.1100', $b->cacheKey('canada_post'));
    }

    public function test_caches_rates_for_15_minutes(): void
    {
        $method = ShippingMethod::factory()->calculated()->create();

        $callCount = 0;
        $fakeDriver = $this->fakeDriver('canada_post', [
            new ShippingRateData('canada_post', 'DOM.EP', 'Expedited Parcel', 1250, null, null),
        ], $callCount);

        $this->service->registerDriver($fakeDriver);

        $shipment = new ShipmentData('K1A 0A6', 'V6B 2W9', 'CA', 1000);
        $methods = collect([$method]);

        $this->service->fetchRates($methods, $shipment);
        $this->service->fetchRates($methods, $shipment);

        // Driver should only be called once; second call hits cache
        $this->assertSame(1, $callCount);
    }

    private function fakeDriver(string $key, array $rates, int &$callCount = 0): ShippingCarrierContract
    {
        return new class($key, $rates, $callCount) implements ShippingCarrierContract
        {
            public function __construct(
                private string $key,
                private array $rateList,
                private int &$callCount,
            ) {}

            public function driverKey(): string
            {
                return $this->key;
            }

            public function getRates(ShipmentData $shipment): Collection
            {
                $this->callCount++;

                return collect($this->rateList);
            }
        };
    }
}
