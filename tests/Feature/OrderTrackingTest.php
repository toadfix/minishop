<?php

namespace Minishop\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Minishop\Database\Seeders\RoleAndPermissionSeeder;
use Minishop\Enums\OrderStatus;
use Minishop\Mail\OrderStatusChangedMail;
use Minishop\Models\Order;
use Minishop\Models\OrderItem;
use Minishop\Models\User;
use Minishop\Tests\TestCase;

class OrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_shipping_stamps_shipped_at_and_delivery_stamps_delivered_at(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Processing]);

        $order->update(['status' => OrderStatus::Shipped]);
        $this->assertNotNull($order->shipped_at);
        $this->assertNull($order->delivered_at);

        $shippedAt = $order->shipped_at;

        $order->update(['status' => OrderStatus::Delivered]);
        $this->assertNotNull($order->delivered_at);
        // Shipped timestamp is not overwritten by the later transition.
        $this->assertTrue($shippedAt->equalTo($order->fresh()->shipped_at));
    }

    public function test_tracking_details_are_persisted(): void
    {
        $order = Order::factory()->create();

        $order->update(['carrier' => 'Canada Post', 'tracking_number' => 'CP123456789CA']);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'carrier' => 'Canada Post',
            'tracking_number' => 'CP123456789CA',
        ]);
    }

    public function test_customer_sees_tracking_on_the_order_page(): void
    {
        $user = User::factory()->customer()->create();
        $order = Order::factory()->for($user->customer)->create([
            'status' => OrderStatus::Shipped,
            'carrier' => 'Canada Post',
            'tracking_number' => 'CP123456789CA',
        ]);

        $this->actingAs($user)
            ->get(route('account.orders.show', $order))
            ->assertOk()
            ->assertSee('CP123456789CA')
            ->assertSee('Canada Post');
    }

    public function test_shipped_email_includes_the_tracking_number(): void
    {
        Mail::fake();
        $user = User::factory()->customer()->create();
        $order = Order::factory()->for($user->customer)->create(['status' => OrderStatus::Processing]);
        OrderItem::factory()->create(['order_id' => $order->id]);

        $order->update([
            'status' => OrderStatus::Shipped,
            'carrier' => 'Canada Post',
            'tracking_number' => 'CP123456789CA',
        ]);

        Mail::assertQueued(OrderStatusChangedMail::class, function (OrderStatusChangedMail $mail) {
            return $mail->render() && str_contains($mail->render(), 'CP123456789CA');
        });
    }
}
