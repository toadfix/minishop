<?php

namespace Minishop\Tests\Feature\Storefront;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Minishop\Enums\OrderStatus;
use Minishop\Models\Order;
use Minishop\Models\ShippingMethod;
use Minishop\Models\StoreSettings;
use Minishop\Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(array $overrides = []): Order
    {
        $shippingMethod = ShippingMethod::factory()->create();

        return Order::factory()->create(array_merge([
            'payment_status' => 'pending',
            'payment_gateway' => 'stripe',
            'shipping_method_id' => $shippingMethod->id,
        ], $overrides));
    }

    public function test_payment_page_renders_for_pending_order(): void
    {
        $order = $this->makeOrder(['payment_gateway' => 'stripe']);

        $this->get(route('storefront.checkout.payment.show', $order->order_number))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('storefront/Payment')
                ->has('order')
            );
    }

    public function test_payment_page_redirects_when_order_already_paid(): void
    {
        $order = $this->makeOrder(['payment_status' => 'paid']);

        $this->get(route('storefront.checkout.payment.show', $order->order_number))
            ->assertRedirect(route('storefront.order.confirmation', $order));
    }

    public function test_stripe_intent_returns_422_when_order_already_paid(): void
    {
        $order = $this->makeOrder(['payment_status' => 'paid', 'payment_gateway' => 'stripe']);

        StoreSettings::current()->update(['stripe_secret_key' => 'sk_test_fake']);

        $this->postJson(route('storefront.checkout.payment.stripe', $order->order_number))
            ->assertStatus(422);
    }

    public function test_paymongo_checkout_returns_checkout_url(): void
    {
        $order = $this->makeOrder(['payment_gateway' => 'paymongo']);

        StoreSettings::current()->update(['paymongo_secret_key' => 'sk_test_paymongo']);

        Http::fake([
            'https://api.paymongo.com/v1/checkout_sessions' => Http::response([
                'data' => [
                    'id' => 'cs_test_123',
                    'attributes' => [
                        'checkout_url' => 'https://checkout.paymongo.com/test',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->postJson(
            route('storefront.checkout.payment.paymongo', $order->order_number)
        );

        $response->assertOk()
            ->assertJsonPath('checkoutUrl', 'https://checkout.paymongo.com/test');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'paymongo.com'));
    }

    public function test_paymongo_checkout_returns_422_when_order_already_paid(): void
    {
        $order = $this->makeOrder(['payment_status' => 'paid', 'payment_gateway' => 'paymongo']);

        StoreSettings::current()->update(['paymongo_secret_key' => 'sk_test_paymongo']);

        $this->postJson(
            route('storefront.checkout.payment.paymongo', $order->order_number)
        )->assertStatus(422);
    }

    public function test_paymongo_callback_marks_order_paid_on_success(): void
    {
        $order = $this->makeOrder(['payment_gateway' => 'paymongo']);

        $this->get(
            route('storefront.checkout.payment.callback', $order->order_number).'?status=success'
        )->assertRedirect(route('storefront.order.confirmation', $order));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'status' => OrderStatus::Processing->value,
        ]);
        $this->assertNotNull($order->fresh()->paid_at);
    }

    public function test_paymongo_callback_redirects_back_on_cancel(): void
    {
        $order = $this->makeOrder(['payment_gateway' => 'paymongo']);

        $this->get(
            route('storefront.checkout.payment.callback', $order->order_number).'?status=cancel'
        )->assertRedirect(route('storefront.checkout.payment.show', $order->order_number));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'pending',
        ]);
    }
}
