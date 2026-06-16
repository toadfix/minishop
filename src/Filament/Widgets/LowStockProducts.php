<?php

namespace Minishop\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Minishop\Support\DashboardMetrics;

class LowStockProducts extends TableWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Low stock')
            ->query(app(DashboardMetrics::class)->lowStockProducts()->limit(10))
            ->paginated(false)
            ->emptyStateHeading('All products are well stocked')
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('sku')->label('SKU')->placeholder('—'),
                TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->badge()
                    ->color(fn (int $state) => $state <= 0 ? 'danger' : 'warning'),
            ]);
    }
}
