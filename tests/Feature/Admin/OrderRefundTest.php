<?php

namespace Minishop\Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Actions\RefundOrderAction;
use Minishop\Enums\OrderStatus;
use Minishop\Models\Order;
use Minishop\Services\StripeRefundService;
use Minishop\Tests\TestCase;
use RuntimeException;

class OrderRefundTest extends TestCase
{
    use RefreshDatabase;

    private function codOrder(int $total = 10000): Order
    {
        return Order::factory()->create([
            'payment_gateway' => 'cod',
            'payment_status' => 'paid',
            'total_amount' => $total,
            'refunded_amount' => 0,
            'status' => OrderStatus::Processing,
        ]);
    }

    public function test_partial_refund_records_amount_without_marking_order_refunded(): void
    {
        $order = $this->codOrder(10000);

        app(RefundOrderAction::class)->execute($order, 4000, 'Damaged item');

        $order->refresh();
        $this->assertSame(4000, $order->refunded_amount);
        $this->assertSame('partially_refunded', $order->payment_status);
        $this->assertSame(OrderStatus::Processing, $order->status);
        $this->assertNotNull($order->refunded_at);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'refunded',
            'subject_type' => 'Order',
            'subject_id' => $order->id,
        ]);
    }

    public function test_full_refund_marks_order_refunded(): void
    {
        $order = $this->codOrder(10000);

        app(RefundOrderAction::class)->execute($order, 10000);

        $order->refresh();
        $this->assertSame(10000, $order->refunded_amount);
        $this->assertSame('refunded', $order->payment_status);
        $this->assertSame(OrderStatus::Refunded, $order->status);
    }

    public function test_cumulative_partial_refunds_cannot_exceed_the_total(): void
    {
        $order = $this->codOrder(10000);
        $action = app(RefundOrderAction::class);

        $action->execute($order, 6000);

        $this->expectException(RuntimeException::class);
        $action->execute($order->refresh(), 5000);
    }

    public function test_refund_amount_must_be_positive(): void
    {
        $order = $this->codOrder();

        $this->expectException(RuntimeException::class);
        app(RefundOrderAction::class)->execute($order, 0);
    }

    public function test_stripe_order_refunds_through_the_gateway(): void
    {
        $this->mock(StripeRefundService::class, function ($mock) {
            $mock->shouldReceive('refund')->once()->andReturn('re_test_123');
        });

        $order = Order::factory()->create([
            'payment_gateway' => 'stripe',
            'payment_intent_id' => 'pi_test_123',
            'payment_status' => 'paid',
            'total_amount' => 8000,
            'refunded_amount' => 0,
        ]);

        app(RefundOrderAction::class)->execute($order, 8000);

        $order->refresh();
        $this->assertSame(8000, $order->refunded_amount);
        $this->assertSame('refunded', $order->payment_status);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'refunded',
            'subject_id' => $order->id,
        ]);
    }
}
