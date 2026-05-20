<?php

namespace Minishop\Tests\Feature\Storefront;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Minishop\Database\Seeders\RoleAndPermissionSeeder;
use Minishop\Models\Product;
use Minishop\Models\ShippingMethod;
use Minishop\Models\StoreSettings;
use Minishop\Services\Shipping\CanadaPostCarrier;
use Minishop\Services\Shipping\ShippingRateService;
use Minishop\Tests\TestCase;

class CheckoutShippingRatesTest extends TestCase
{
    use RefreshDatabase;

    private string $url;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->url = route('storefront.checkout.shipping-rates');
    }

    private function validPayload(array $overrides = []): array
    {
        $product = Product::factory()->create();

        return array_merge([
            'postcode' => 'K1A 0A6',
            'country' => 'CA',
            'items' => [
                ['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1],
            ],
        ], $overrides);
    }

    public function test_requires_postcode(): void
    {
        $product = Product::factory()->create();

        $this->postJson($this->url, [
            'country' => 'CA',
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['postcode']);
    }

    public function test_requires_country(): void
    {
        $product = Product::factory()->create();

        $this->postJson($this->url, [
            'postcode' => 'K1A 0A6',
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['country']);
    }

    public function test_requires_items(): void
    {
        $this->postJson($this->url, ['postcode' => 'K1A 0A6', 'country' => 'CA', 'items' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
    }

    public function test_requires_quantity_max_999(): void
    {
        $product = Product::factory()->create();

        $this->postJson($this->url, [
            'postcode' => 'K1A 0A6',
            'country' => 'CA',
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1000]],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.quantity']);
    }

    public function test_returns_flat_rate_methods_without_api_call(): void
    {
        Http::preventStrayRequests();

        $method = ShippingMethod::factory()->create(['name' => 'Standard', 'price' => 20000, 'is_free' => false]);

        $response = $this->postJson($this->url, $this->validPayload());

        $response->assertOk()
            ->assertJsonCount(1, 'rates')
            ->assertJsonFragment([
                'shipping_method_id' => $method->id,
                'type' => 'flat_rate',
                'amount_cents' => 20000,
            ]);
    }

    public function test_returns_calculated_rates_when_canada_post_is_configured(): void
    {
        ShippingMethod::factory()->calculated()->create();

        StoreSettings::current()->update(['origin_postcode' => 'K1A 0A6']);

        $xml = $this->canadaPostXmlResponse();
        Http::fake(['*canadapost*' => Http::response($xml, 200)]);

        $this->bindShippingServiceWithCanadaPost();

        $response = $this->postJson($this->url, $this->validPayload());

        $response->assertOk();
        $calculatedRates = collect($response->json('rates'))->where('type', 'calculated');
        $this->assertNotEmpty($calculatedRates);
    }

    public function test_degrades_gracefully_when_canada_post_api_fails(): void
    {
        ShippingMethod::factory()->create(['name' => 'Standard', 'price' => 20000]);
        ShippingMethod::factory()->calculated()->create();

        StoreSettings::current()->update(['origin_postcode' => 'K1A 0A6']);

        Http::fake(['*canadapost*' => Http::response('Error', 503)]);

        $this->bindShippingServiceWithCanadaPost();

        $response = $this->postJson($this->url, $this->validPayload());

        // Should succeed and return at least the flat-rate methods
        $response->assertOk();
        $flatRates = collect($response->json('rates'))->where('type', 'flat_rate');
        $this->assertNotEmpty($flatRates);
    }

    public function test_rates_are_cached_for_15_minutes(): void
    {
        // Use a fixed product so both requests produce the same cache key (same weight)
        $product = Product::factory()->create(['weight_grams' => 500]);
        ShippingMethod::factory()->calculated()->create(['service_code' => 'DOM.EP']);

        StoreSettings::current()->update(['origin_postcode' => 'K1A 0A6']);

        $xml = $this->canadaPostXmlResponse();
        Http::fake(['*canadapost*' => Http::response($xml, 200)]);

        $this->bindShippingServiceWithCanadaPost();

        $payload = [
            'postcode' => 'V6B 2W9',
            'country' => 'CA',
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
        ];

        // First request — hits the API and caches
        $this->postJson($this->url, $payload)->assertOk();
        Http::assertSentCount(1);

        // Second identical request — should hit the cache, not the API
        $this->postJson($this->url, $payload)->assertOk();
        Http::assertSentCount(1);
    }

    public function test_stores_quote_in_session_after_fetch(): void
    {
        ShippingMethod::factory()->calculated()->create(['service_code' => 'DOM.EP']);

        StoreSettings::current()->update(['origin_postcode' => 'K1A 0A6']);

        Http::fake(['*canadapost*' => Http::response($this->canadaPostXmlResponse(), 200)]);
        $this->bindShippingServiceWithCanadaPost();

        $this->postJson($this->url, $this->validPayload())->assertOk();

        $this->assertNotNull(session('shipping_quotes'), 'Expected shipping_quotes to be in session');
        $this->assertNotEmpty(session('shipping_quotes'));
        $this->assertSame('DOM.EP', session('shipping_quotes.0.service_code'));
    }

    private function bindShippingServiceWithCanadaPost(): void
    {
        config([
            'services.canada_post.username' => 'test_user',
            'services.canada_post.password' => 'test_pass',
            'services.canada_post.customer_number' => '1234567',
            'services.canada_post.sandbox' => true,
        ]);

        $service = new ShippingRateService;
        $service->registerDriver(new CanadaPostCarrier);
        $this->app->instance(ShippingRateService::class, $service);
    }

    private function canadaPostXmlResponse(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<price-quotes xmlns="http://www.canadapost.ca/ws/ship/rate-v4">
  <price-quote>
    <service-code>DOM.EP</service-code>
    <service-name>Expedited Parcel</service-name>
    <price-details>
      <due>12.50</due>
    </price-details>
    <service-standard>
      <expected-delivery-date>2026-04-07</expected-delivery-date>
    </service-standard>
  </price-quote>
  <price-quote>
    <service-code>DOM.XP</service-code>
    <service-name>Xpresspost</service-name>
    <price-details>
      <due>25.00</due>
    </price-details>
    <service-standard>
      <expected-delivery-date>2026-04-05</expected-delivery-date>
    </service-standard>
  </price-quote>
</price-quotes>
XML;
    }
}
