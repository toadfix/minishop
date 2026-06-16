<?php

namespace Minishop\Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Enums\OrderStatus;
use Minishop\Models\Order;
use Minishop\Models\Product;
use Minishop\Models\StoreSettings;
use Minishop\Support\DashboardMetrics;
use Minishop\Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    private DashboardMetrics $metrics;

    protected function setUp(): void
    {
        parent::setUp();
        $this->metrics = new DashboardMetrics;
    }

    public function test_net_revenue_counts_paid_orders_minus_refunds(): void
    {
        Order::factory()->create(['paid_at' => now(), 'total_amount' => 10000, 'refunded_amount' => 2000]);
        Order::factory()->create(['paid_at' => now(), 'total_amount' => 5000, 'refunded_amount' => 0]);
        Order::factory()->create(['paid_at' => null, 'total_amount' => 9999]); // never paid — excluded

        $revenue = $this->metrics->netRevenueBetween(now()->startOfMonth(), now());

        $this->assertSame(13000, $revenue); // (10000-2000) + 5000
    }

    public function test_net_revenue_respects_the_date_window(): void
    {
        Order::factory()->create(['paid_at' => now(), 'total_amount' => 5000, 'refunded_amount' => 0]);
        Order::factory()->create(['paid_at' => now()->subMonths(2), 'total_amount' => 8000, 'refunded_amount' => 0]);

        $this->assertSame(5000, $this->metrics->netRevenueBetween(now()->startOfMonth(), now()));
    }

    public function test_orders_by_status_groups_counts(): void
    {
        Order::factory()->count(2)->create(['status' => OrderStatus::Pending]);
        Order::factory()->create(['status' => OrderStatus::Delivered]);

        $byStatus = $this->metrics->ordersByStatus();

        $this->assertSame(2, $byStatus['pending'] ?? 0);
        $this->assertSame(1, $byStatus['delivered'] ?? 0);
    }

    public function test_revenue_by_day_is_zero_filled_for_the_window(): void
    {
        Order::factory()->create(['paid_at' => now(), 'total_amount' => 4000, 'refunded_amount' => 0]);

        $series = $this->metrics->revenueByDay(7);

        $this->assertCount(7, $series);
        $this->assertSame(4000, $series[now()->toDateString()]);
    }

    public function test_low_stock_query_returns_products_at_or_below_threshold(): void
    {
        StoreSettings::current()->update(['low_stock_threshold' => 5]);
        $low = Product::factory()->simple()->create(['stock_quantity' => 2, 'is_active' => true]);
        Product::factory()->simple()->create(['stock_quantity' => 50, 'is_active' => true]);

        $results = $this->metrics->lowStockProducts()->pluck('id');

        $this->assertTrue($results->contains($low->id));
        $this->assertCount(1, $results);
    }
}
