<?php

namespace Minishop\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Minishop\Support\DashboardMetrics;

class RevenueChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Revenue (last 30 days)';

    public static function canView(): bool
    {
        return auth()->user()?->can('dashboard.revenue') ?? false;
    }

    protected function getData(): array
    {
        $series = app(DashboardMetrics::class)->revenueByDay(30);

        return [
            'datasets' => [[
                'label' => 'Net revenue',
                'data' => array_map(fn (int $cents) => round($cents / 100, 2), array_values($series)),
                'borderColor' => '#6366f1',
                'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                'fill' => true,
                'tension' => 0.3,
            ]],
            'labels' => array_map(fn (string $date) => Carbon::parse($date)->format('M j'), array_keys($series)),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
