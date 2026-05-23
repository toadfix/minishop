<?php

namespace Minishop\Tests\Feature\Storefront;

use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->withSession(['checkout_order_id' => $order->id])
            ->get(route('storefront.checkout.payment.show', $order->order_number))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('storefront/Payment')
                ->has('order')
            );
    }

    public function test_payment_page_returns_403_without_session_or_auth(): void
    {
        $order = $this->makeOrder(['payment_gateway' => 'stripe']);

        $this->get(route('storefront.checkout.payment.show', $order->order_number))
            ->assertForbidden();
    }

    public function test_payment_page_redirects_when_order_already_paid(): void
    {
        $order = $this->makeOrder(['payment_status' => 'paid']);

        $this->withSession(['checkout_order_id' => $order->id])
            ->get(route('storefront.checkout.payment.show', $order->order_number))
            ->assertRedirect(route('storefront.order.confirmation', $order));
    }

    public function test_stripe_intent_returns_422_when_order_already_paid(): void
    {
        $order = $this->makeOrder(['payment_status' => 'paid', 'payment_gateway' => 'stripe']);

        StoreSettings::current()->update(['stripe_secret_key' => 'sk_test_fake']);

        $this->withSession(['checkout_order_id' => $order->id])
            ->postJson(route('storefront.checkout.payment.stripe', $order->order_number))
            ->assertStatus(422);
    }

    public function test_stripe_intent_returns_403_without_session_or_auth(): void
    {
        $order = $this->makeOrder(['payment_gateway' => 'stripe']);

        $this->postJson(route('storefront.checkout.payment.stripe', $order->order_number))
            ->assertForbidden();
    }
}
