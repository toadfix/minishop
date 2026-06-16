<?php

namespace Minishop\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Minishop\Support\DashboardMetrics;
use Minishop\Support\Money;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $metrics = app(DashboardMetrics::class);

        $monthStart = now()->startOfMonth();
        $now = now();
        $lastMonthStart = now()->subMonthNoOverflow()->startOfMonth();
        $lastMonthEnd = now()->subMonthNoOverflow()->startOfMonth()->addDays($now->day - 1)->endOfDay();

        $stats = [];

        if (auth()->user()?->can('dashboard.revenue')) {
            $revenue = $metrics->netRevenueBetween($monthStart, $now);
            $lastRevenue = $metrics->netRevenueBetween($lastMonthStart, $lastMonthEnd);

            $stats[] = Stat::make('Revenue this month', Money::format($revenue))
                ->description($this->trend($revenue, $lastRevenue))
                ->descriptionIcon($revenue >= $lastRevenue ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenue >= $lastRevenue ? 'success' : 'danger');
        }

        $orders = $metrics->ordersCountBetween($monthStart, $now);
        $lastOrders = $metrics->ordersCountBetween($lastMonthStart, $lastMonthEnd);
        $stats[] = Stat::make('Orders this month', (string) $orders)
            ->description($this->trend($orders, $lastOrders))
            ->descriptionIcon($orders >= $lastOrders ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->color($orders >= $lastOrders ? 'success' : 'gray');

        $customers = $metrics->newCustomersBetween($monthStart, $now);
        $lastCustomers = $metrics->newCustomersBetween($lastMonthStart, $lastMonthEnd);
        $stats[] = Stat::make('New customers', (string) $customers)
            ->description($this->trend($customers, $lastCustomers))
            ->color('info');

        $attention = $metrics->attentionCount();
        $stats[] = Stat::make('Needs attention', (string) $attention)
            ->description('Pending orders, reviews & open returns')
            ->color($attention > 0 ? 'warning' : 'gray');

        return $stats;
    }

    private function trend(int|float $current, int|float $previous): string
    {
        if ($previous <= 0) {
            return $current > 0 ? 'New this month' : 'vs last month';
        }

        $change = round((($current - $previous) / $previous) * 100);

        return ($change >= 0 ? '+' : '').$change.'% vs last month';
    }
}
