<?php

namespace Minishop\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Minishop\Enums\OrderStatus;
use Minishop\Enums\ProductType;
use Minishop\Enums\ReturnStatus;
use Minishop\Enums\ReviewStatus;
use Minishop\Models\Customer;
use Minishop\Models\Order;
use Minishop\Models\OrderReturn;
use Minishop\Models\Product;
use Minishop\Models\Review;
use Minishop\Models\StoreSettings;

/**
 * Read-only aggregates for the admin dashboard widgets. Kept separate from the
 * widgets so the query logic is unit-testable (widgets can't be rendered in the
 * package's Testbench setup). Revenue is net of refunds and counts any order
 * that has been paid (paid_at set), keyed by payment date.
 */
class DashboardMetrics
{
    public function netRevenueBetween(Carbon $start, Carbon $end): int
    {
        return (int) Order::query()
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$start, $end])
            ->sum(DB::raw('total_amount - refunded_amount'));
    }

    public function ordersCountBetween(Carbon $start, Carbon $end): int
    {
        return Order::query()->whereBetween('created_at', [$start, $end])->count();
    }

    public function newCustomersBetween(Carbon $start, Carbon $end): int
    {
        return Customer::query()->whereBetween('created_at', [$start, $end])->count();
    }

    /**
     * Items awaiting an admin: pending orders + pending reviews + open returns.
     */
    public function attentionCount(): int
    {
        $pendingOrders = Order::query()->where('status', OrderStatus::Pending)->count();
        $pendingReviews = Review::query()->where('status', ReviewStatus::Pending)->count();
        $openReturns = OrderReturn::query()
            ->whereIn('status', [ReturnStatus::Requested, ReturnStatus::Approved, ReturnStatus::Received])
            ->count();

        return $pendingOrders + $pendingReviews + $openReturns;
    }

    /**
     * Net revenue per day for the last $days days (oldest first), zero-filled.
     *
     * @return array<string, int> date (Y-m-d) => cents
     */
    public function revenueByDay(int $days = 30): array
    {
        $start = now()->subDays($days - 1)->startOfDay();

        $byDate = Order::query()
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', $start)
            ->get(['paid_at', 'total_amount', 'refunded_amount'])
            ->groupBy(fn (Order $o) => $o->paid_at->toDateString())
            ->map(fn ($group) => (int) $group->sum(fn (Order $o) => $o->total_amount - $o->refunded_amount));

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i)->toDateString();
            $series[$date] = $byDate->get($date, 0);
        }

        return $series;
    }

    /**
     * @return array<string, int> status value => order count
     */
    public function ordersByStatus(): array
    {
        return Order::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    public function lowStockProducts(): Builder
    {
        $threshold = StoreSettings::current()->low_stock_threshold ?? 0;

        return Product::query()
            ->where('is_active', true)
            ->where('type', ProductType::Simple)
            ->where('stock_quantity', '<=', $threshold)
            ->orderBy('stock_quantity');
    }
}
