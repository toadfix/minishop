<?php

namespace Minishop\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Minishop\Enums\OrderStatus;
use Minishop\Support\DashboardMetrics;

class OrdersByStatusChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Orders by status';

    protected function getData(): array
    {
        $counts = app(DashboardMetrics::class)->ordersByStatus();

        $labels = [];
        $data = [];
        foreach (OrderStatus::cases() as $status) {
            $labels[] = $status->label();
            $data[] = $counts[$status->value] ?? 0;
        }

        return [
            'datasets' => [[
                'label' => 'Orders',
                'data' => $data,
                'backgroundColor' => ['#f59e0b', '#3b82f6', '#6366f1', '#22c55e', '#ef4444', '#9ca3af'],
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
