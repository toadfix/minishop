<?php

namespace Minishop\Tests\Feature\Storefront;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Models\Customer;
use Minishop\Models\Order;
use Minishop\Models\OrderItem;
use Minishop\Models\Product;
use Minishop\Models\User;
use Minishop\Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ga4_snippet_is_injected_when_a_measurement_id_is_configured(): void
    {
        config(['minishop.analytics.ga4_measurement_id' => 'G-TEST123']);
        Product::factory()->create();

        $this->get(route('storefront.products.index'))
            ->assertOk()
            ->assertSee('googletagmanager.com/gtag/js?id=G-TEST123')
            ->assertSee("gtag('config'", false);
    }

    public function test_no_analytics_snippet_when_measurement_id_is_unset(): void
    {
        config(['minishop.analytics.ga4_measurement_id' => null]);
        Product::factory()->create();

        $this->get(route('storefront.products.index'))
            ->assertOk()
            ->assertDontSee('googletagmanager.com/gtag/js');
    }

    public function test_purchase_event_fires_on_order_confirmation(): void
    {
        config(['minishop.analytics.ga4_measurement_id' => 'G-TEST123']);

        $user = User::factory()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'total_amount' => 12500,
        ]);
        OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 2]);

        $this->withSession(['checkout_order_id' => $order->id])
            ->get(route('storefront.order.confirmation', $order))
            ->assertOk()
            ->assertSee("gtag('event', 'purchase'", false)
            ->assertSee($order->order_number)
            ->assertSee('125'); // value 125.0
    }

    public function test_no_purchase_event_when_analytics_disabled(): void
    {
        config(['minishop.analytics.ga4_measurement_id' => null]);

        $user = User::factory()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        OrderItem::factory()->create(['order_id' => $order->id]);

        $this->withSession(['checkout_order_id' => $order->id])
            ->get(route('storefront.order.confirmation', $order))
            ->assertOk()
            ->assertDontSee("'purchase'", false);
    }
}
