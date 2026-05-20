<?php

namespace Minishop\Tests\Feature\Storefront;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Minishop\Models\Coupon;
use Minishop\Models\Order;
use Minishop\Models\Product;
use Minishop\Models\ProductVariant;
use Minishop\Models\ShippingMethod;
use Minishop\Models\StoreSettings;
use Minishop\Models\TaxZone;
use Minishop\Models\TaxZoneRate;
use Minishop\Models\User;
use Minishop\Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private ?ShippingMethod $shippingMethod = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shippingMethod = ShippingMethod::factory()->create(['price' => 20000, 'is_free' => false]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'address_line1' => '123 Rizal St.',
            'city' => 'Makati',
            'state' => 'Metro Manila',
            'postcode' => '1200',
            'country' => 'CA',
            'shipping_method_id' => $this->shippingMethod->id,
            'items' => [],
        ], $overrides);
    }

    public function test_checkout_page_renders(): void
    {
        $this->get(route('storefront.checkout.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('storefront/Checkout'));
    }

    public function test_order_is_created_with_valid_data(): void
    {
        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 10]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 2]],
        ]))->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'shipping_name' => 'Maria Santos',
            'shipping_method_id' => $this->shippingMethod->id,
        ]);
        $this->assertDatabaseHas('order_items', ['product_id' => $product->id, 'quantity' => 2]);
    }

    public function test_order_stores_shipping_amount_from_selected_method(): void
    {
        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 5]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
        ]));

        $this->assertDatabaseHas('orders', ['shipping_amount' => 20000]);
    }

    public function test_free_shipping_sets_shipping_amount_to_zero(): void
    {
        $freeMethod = ShippingMethod::factory()->free()->create();
        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 5]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
            'shipping_method_id' => $freeMethod->id,
        ]));

        $this->assertDatabaseHas('orders', ['shipping_amount' => 0]);
    }

    public function test_order_fails_when_shipping_method_missing(): void
    {
        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 5]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
            'shipping_method_id' => null,
        ]))->assertSessionHasErrors('shipping_method_id');
    }

    public function test_order_fails_when_shipping_method_does_not_exist(): void
    {
        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 5]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
            'shipping_method_id' => 99999,
        ]))->assertSessionHasErrors('shipping_method_id');
    }

    public function test_stock_is_decremented_after_order(): void
    {
        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 10]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 3]],
        ]));

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 7]);
    }

    public function test_order_fails_validation_when_items_empty(): void
    {
        $this->post(route('storefront.checkout.store'), $this->validPayload(['items' => []]))
            ->assertSessionHasErrors('items');
    }

    public function test_order_fails_when_required_fields_missing(): void
    {
        $this->post(route('storefront.checkout.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'address_line1', 'city', 'state', 'postcode', 'country', 'items']);
    }

    public function test_order_fails_when_insufficient_stock(): void
    {
        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 1]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 5]],
        ]))->assertStatus(422);
    }

    public function test_coupon_discount_is_applied(): void
    {
        $product = Product::factory()->create(['price' => 10000, 'stock_quantity' => 5]);
        Coupon::factory()->percentage()->create(['code' => 'SAVE10', 'value' => 10]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
            'coupon_code' => 'SAVE10',
        ]));

        $this->assertDatabaseHas('orders', ['discount_amount' => 1000]);
    }

    public function test_coupon_used_count_is_incremented(): void
    {
        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 5]);
        $coupon = Coupon::factory()->percentage()->create(['code' => 'DISC20', 'value' => 20]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
            'coupon_code' => 'DISC20',
        ]));

        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'used_count' => 1]);
    }

    public function test_guest_user_and_customer_are_created_for_new_email(): void
    {
        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 5]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
        ]));

        $this->assertDatabaseHas('users', ['email' => 'maria@example.com']);
        $user = User::query()->where('email', 'maria@example.com')->first();
        $this->assertDatabaseHas('customers', ['user_id' => $user->id]);
    }

    public function test_second_order_reuses_existing_user_and_customer(): void
    {
        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 20]);
        $payload = $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
        ]);

        $this->post(route('storefront.checkout.store'), $payload);
        $this->post(route('storefront.checkout.store'), $payload);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('orders', 2);
    }

    public function test_shipping_amount_is_resolved_from_session_quote(): void
    {
        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 5]);
        $calculatedMethod = ShippingMethod::factory()->calculated()->create(['service_code' => 'DOM.EP']);

        // Pre-seed the session with a quote as the shipping-rates endpoint would
        $this->withSession([
            'shipping_quotes' => [
                ['carrier' => 'canada_post', 'service_code' => 'DOM.EP', 'amount_cents' => 1250],
            ],
        ]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'shipping_method_id' => $calculatedMethod->id,
            'carrier' => 'canada_post',
            'service_code' => 'DOM.EP',
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
        ]))->assertRedirect();

        $this->assertDatabaseHas('orders', ['shipping_amount' => 1250]);
    }

    public function test_calculated_shipping_falls_back_to_zero_when_no_quote_in_session(): void
    {
        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 5]);
        $calculatedMethod = ShippingMethod::factory()->calculated()->create();

        // No shipping_quotes in session — rates were never fetched or session expired
        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'shipping_method_id' => $calculatedMethod->id,
            'carrier' => 'canada_post',
            'service_code' => 'DOM.EP',
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
        ]))->assertRedirect();

        $this->assertDatabaseHas('orders', ['shipping_amount' => 0]);
    }

    public function test_calculated_shipping_is_zero_when_service_code_does_not_match_quote(): void
    {
        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 5]);
        $calculatedMethod = ShippingMethod::factory()->calculated()->create(['service_code' => 'DOM.EP']);

        // Session has DOM.XP but request submits DOM.EP — attacker trying to swap to cheaper rate
        $this->withSession([
            'shipping_quotes' => [
                ['carrier' => 'canada_post', 'service_code' => 'DOM.XP', 'amount_cents' => 2500],
            ],
        ]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'shipping_method_id' => $calculatedMethod->id,
            'carrier' => 'canada_post',
            'service_code' => 'DOM.EP',
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
        ]))->assertRedirect();

        $this->assertDatabaseHas('orders', ['shipping_amount' => 0]);
    }

    public function test_confirmation_page_renders_with_order(): void
    {
        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 5]);

        $response = $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
        ]));

        $confirmationUrl = $response->headers->get('Location');

        $this->get($confirmationUrl)
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('storefront/OrderConfirmation'));
    }

    public function test_order_stores_tax_zone_name_and_breakdown_when_zone_based(): void
    {
        StoreSettings::query()->updateOrCreate([], ['tax_mode' => 'zone_based', 'tax_rate' => 0]);
        Cache::flush();

        $zone = TaxZone::factory()->ontario()->create();
        TaxZoneRate::factory()->hst()->for($zone, 'zone')->create();

        $product = Product::factory()->create(['price' => 10000, 'stock_quantity' => 5]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'state' => 'ON',
            'country' => 'CA',
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
        ]));

        $this->assertDatabaseHas('orders', [
            'tax_zone_name' => 'Ontario',
        ]);
    }

    public function test_order_tax_zone_name_is_null_when_flat_rate(): void
    {
        StoreSettings::query()->updateOrCreate([], ['tax_mode' => 'flat_rate', 'tax_rate' => 13.0]);
        Cache::flush();

        $product = Product::factory()->create(['price' => 10000, 'stock_quantity' => 5]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
        ]));

        $this->assertDatabaseHas('orders', [
            'tax_zone_name' => null,
        ]);
    }

    public function test_order_stores_correct_tax_breakdown_json_structure(): void
    {
        StoreSettings::query()->updateOrCreate([], ['tax_mode' => 'zone_based', 'tax_rate' => 0]);
        Cache::flush();

        $zone = TaxZone::factory()->ontario()->create();
        TaxZoneRate::factory()->hst()->for($zone, 'zone')->create();

        $product = Product::factory()->create(['price' => 10000, 'stock_quantity' => 5]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'state' => 'ON',
            'country' => 'CA',
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
        ]));

        $order = Order::query()->latest()->first();

        $this->assertNotNull($order->tax_breakdown);
        $this->assertIsArray($order->tax_breakdown);
        $this->assertArrayHasKey('name', $order->tax_breakdown[0]);
        $this->assertArrayHasKey('rate', $order->tax_breakdown[0]);
        $this->assertArrayHasKey('amount_cents', $order->tax_breakdown[0]);
        $this->assertEquals('HST', $order->tax_breakdown[0]['name']);
    }

    public function test_order_persists_tax_amounts_in_cents(): void
    {
        StoreSettings::query()->updateOrCreate([], ['tax_mode' => 'zone_based', 'tax_rate' => 0]);
        Cache::flush();

        $zone = TaxZone::factory()->ontario()->create();
        TaxZoneRate::factory()->hst()->for($zone, 'zone')->create();

        // 1 × $100 product → subtotal = 10000 cents → HST 13% = 1300 cents
        $product = Product::factory()->create(['price' => 10000, 'stock_quantity' => 5]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'state' => 'ON',
            'country' => 'CA',
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
        ]));

        $this->assertDatabaseHas('orders', [
            'tax_amount' => 1300,
            'subtotal' => 10000,
        ]);
    }

    // ── Bundle checkout ─────────────────────────────────────────────────────

    public function test_checkout_fails_when_bundle_component_is_out_of_stock(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create(['price' => 5000]);
        $componentA = Product::factory()->create(['stock_quantity' => 10]);
        $componentB = Product::factory()->create(['stock_quantity' => 0]);

        $bundle->bundleItems()->create(['component_product_id' => $componentA->id, 'quantity' => 1]);
        $bundle->bundleItems()->create(['component_product_id' => $componentB->id, 'quantity' => 1]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $bundle->id, 'variant_id' => null, 'quantity' => 1]],
        ]))->assertStatus(422);
    }

    public function test_checkout_fails_when_bundle_component_variant_is_out_of_stock(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create(['price' => 5000]);
        $component = Product::factory()->variable()->create(['stock_quantity' => 100]);
        $variant = ProductVariant::factory()->for($component)->create(['stock_quantity' => 0]);

        $bundle->bundleItems()->create([
            'component_product_id' => $component->id,
            'component_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $bundle->id, 'variant_id' => null, 'quantity' => 1]],
        ]))->assertStatus(422);
    }

    public function test_checkout_with_bundled_product_quantity_greater_than_component_allows(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create(['price' => 3000]);
        $component = Product::factory()->create(['stock_quantity' => 5]);

        $bundle->bundleItems()->create(['component_product_id' => $component->id, 'quantity' => 2]);
        // effective stock = floor(5/2) = 2, ordering 3 should fail

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $bundle->id, 'variant_id' => null, 'quantity' => 3]],
        ]))->assertStatus(422);
    }

    public function test_bundle_stock_decrement_multiplies_component_qty_by_order_qty(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create(['price' => 5000]);
        $componentA = Product::factory()->create(['stock_quantity' => 20]);
        $componentB = Product::factory()->create(['stock_quantity' => 30]);

        $bundle->bundleItems()->create(['component_product_id' => $componentA->id, 'quantity' => 2]);
        $bundle->bundleItems()->create(['component_product_id' => $componentB->id, 'quantity' => 3]);

        // Order 2 bundles: componentA decrements 2*2=4, componentB decrements 3*2=6
        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $bundle->id, 'variant_id' => null, 'quantity' => 2]],
        ]))->assertRedirect();

        $this->assertDatabaseHas('products', ['id' => $componentA->id, 'stock_quantity' => 16]);
        $this->assertDatabaseHas('products', ['id' => $componentB->id, 'stock_quantity' => 24]);
    }

    public function test_bundle_stock_decrement_handles_shared_component(): void
    {
        $sharedComponent = Product::factory()->create(['stock_quantity' => 50]);

        $bundleA = Product::factory()->bundledEmpty()->create(['price' => 3000]);
        $bundleA->bundleItems()->create(['component_product_id' => $sharedComponent->id, 'quantity' => 2]);

        $bundleB = Product::factory()->bundledEmpty()->create(['price' => 4000]);
        $bundleB->bundleItems()->create(['component_product_id' => $sharedComponent->id, 'quantity' => 3]);

        // Order 1 of each: shared decrements 2*1 + 3*1 = 5
        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [
                ['product_id' => $bundleA->id, 'variant_id' => null, 'quantity' => 1],
                ['product_id' => $bundleB->id, 'variant_id' => null, 'quantity' => 1],
            ],
        ]))->assertRedirect();

        $this->assertDatabaseHas('products', ['id' => $sharedComponent->id, 'stock_quantity' => 45]);
    }

    public function test_mixed_order_with_simple_and_bundled_products(): void
    {
        $simple = Product::factory()->simple()->create(['price' => 1000, 'stock_quantity' => 10]);

        $bundle = Product::factory()->bundledEmpty()->create(['price' => 5000]);
        $component = Product::factory()->create(['stock_quantity' => 20]);
        $bundle->bundleItems()->create(['component_product_id' => $component->id, 'quantity' => 2]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [
                ['product_id' => $simple->id, 'variant_id' => null, 'quantity' => 3],
                ['product_id' => $bundle->id, 'variant_id' => null, 'quantity' => 2],
            ],
        ]))->assertRedirect();

        $this->assertDatabaseHas('products', ['id' => $simple->id, 'stock_quantity' => 7]);
        $this->assertDatabaseHas('products', ['id' => $component->id, 'stock_quantity' => 16]);
        $this->assertDatabaseCount('order_items', 2);
    }

    public function test_bundled_product_order_uses_bundle_price_not_component_prices(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create(['price' => 8000]);
        $component = Product::factory()->create(['price' => 5000, 'stock_quantity' => 10]);
        $bundle->bundleItems()->create(['component_product_id' => $component->id, 'quantity' => 1]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $bundle->id, 'variant_id' => null, 'quantity' => 1]],
        ]))->assertRedirect();

        $this->assertDatabaseHas('order_items', [
            'product_id' => $bundle->id,
            'unit_price' => 8000,
            'subtotal' => 8000,
        ]);
    }

    public function test_on_sale_product_applies_discount_at_checkout(): void
    {
        Cache::forget('store_settings');
        StoreSettings::current()->update(['sale_discount_percentage' => 20]);

        $product = Product::factory()->simple()->onSale()->create(['price' => 10000, 'stock_quantity' => 5]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 2]],
        ]))->assertRedirect();

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'unit_price' => 8000,
            'subtotal' => 16000,
        ]);
    }

    public function test_on_sale_product_with_zero_discount_uses_full_price(): void
    {
        Cache::forget('store_settings');
        StoreSettings::current()->update(['sale_discount_percentage' => 0]);

        $product = Product::factory()->simple()->onSale()->create(['price' => 5000, 'stock_quantity' => 5]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
        ]))->assertRedirect();

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'unit_price' => 5000,
        ]);
    }

    public function test_non_on_sale_product_ignores_discount(): void
    {
        Cache::forget('store_settings');
        StoreSettings::current()->update(['sale_discount_percentage' => 25]);

        $product = Product::factory()->simple()->create(['price' => 4000, 'stock_quantity' => 5, 'on_sale' => false]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
        ]))->assertRedirect();

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'unit_price' => 4000,
        ]);
    }

    public function test_variant_on_sale_product_discounts_variant_price(): void
    {
        Cache::forget('store_settings');
        StoreSettings::current()->update(['sale_discount_percentage' => 10]);

        $product = Product::factory()->variable()->onSale()->create(['price' => 5000, 'stock_quantity' => 0]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 6000,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 1]],
        ]))->assertRedirect();

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'unit_price' => 5400,
        ]);
    }
}
