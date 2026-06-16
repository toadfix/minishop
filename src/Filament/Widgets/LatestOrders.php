<?php

namespace Minishop\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Minishop\Enums\OrderStatus;
use Minishop\Models\Order;

class LatestOrders extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Latest orders')
            ->query(Order::query()->with('customer.user')->latest()->limit(10))
            ->paginated(false)
            ->columns([
                TextColumn::make('order_number')->label('Order #'),
                TextColumn::make('customer.user.name')->label('Customer')->placeholder('—'),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => '$'.number_format($state / 100, 2)),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state) => $state->label())
                    ->color(fn (OrderStatus $state) => match ($state) {
                        OrderStatus::Pending => 'warning',
                        OrderStatus::Processing => 'info',
                        OrderStatus::Shipped => 'primary',
                        OrderStatus::Delivered => 'success',
                        OrderStatus::Cancelled => 'danger',
                        OrderStatus::Refunded => 'gray',
                    }),
                TextColumn::make('created_at')->dateTime()->label('Placed'),
            ]);
    }
}
