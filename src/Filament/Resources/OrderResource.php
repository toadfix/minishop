<?php

namespace Minishop\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Minishop\Actions\GenerateInvoicePdf;
use Minishop\Enums\OrderStatus;
use Minishop\Filament\Resources\OrderResource\Pages;
use Minishop\Filament\Resources\OrderResource\RelationManagers\ItemsRelationManager;
use Minishop\Models\Order;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-shopping-bag';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Commerce';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('customer_id')
                ->relationship('customer.user', 'name')
                ->searchable()
                ->preload()
                ->required(),

            Select::make('status')
                ->options(collect(OrderStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                ->required()
                ->default(OrderStatus::Pending->value),

            Select::make('shipping_method_id')
                ->relationship('shippingMethod', 'name')
                ->searchable()
                ->preload()
                ->nullable(),

            TextInput::make('shipping_name')
                ->maxLength(255),

            TextInput::make('shipping_address_line1')
                ->label('Address Line 1')
                ->maxLength(255),

            TextInput::make('shipping_address_line2')
                ->label('Address Line 2')
                ->maxLength(255)
                ->nullable(),

            TextInput::make('shipping_city')
                ->label('City')
                ->maxLength(255),

            TextInput::make('shipping_state')
                ->label('Province/State')
                ->maxLength(255),

            TextInput::make('shipping_postcode')
                ->label('Postal Code')
                ->maxLength(20),

            TextInput::make('shipping_country')
                ->label('Country')
                ->maxLength(2)
                ->default('CA'),

            Textarea::make('notes')
                ->columnSpanFull()
                ->nullable(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Order Details')
                ->schema([
                    TextEntry::make('order_number')
                        ->label('Order #'),

                    TextEntry::make('status')
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

                    TextEntry::make('customer.user.name')
                        ->label('Customer'),

                    TextEntry::make('customer.user.email')
                        ->label('Email'),

                    TextEntry::make('created_at')
                        ->dateTime()
                        ->label('Placed At'),
                ])
                ->columns(2),

            Section::make('Amounts')
                ->schema([
                    TextEntry::make('subtotal')
                        ->formatStateUsing(fn ($state) => '$'.number_format($state / 100, 2)),

                    TextEntry::make('discount_amount')
                        ->label('Discount')
                        ->formatStateUsing(fn ($state) => '$'.number_format($state / 100, 2)),

                    TextEntry::make('shipping_amount')
                        ->label('Shipping')
                        ->formatStateUsing(fn ($state) => '$'.number_format($state / 100, 2)),

                    TextEntry::make('tax_amount')
                        ->label('Tax')
                        ->formatStateUsing(fn ($state) => '$'.number_format($state / 100, 2)),

                    TextEntry::make('total_amount')
                        ->label('Total')
                        ->formatStateUsing(fn ($state) => '$'.number_format($state / 100, 2))
                        ->weight('bold'),
                ])
                ->columns(3),

            Section::make('Shipping Address')
                ->schema([
                    TextEntry::make('shipping_name')
                        ->label('Name'),

                    TextEntry::make('shipping_address_line1')
                        ->label('Address'),

                    TextEntry::make('shipping_city')
                        ->label('City'),

                    TextEntry::make('shipping_state')
                        ->label('Province'),

                    TextEntry::make('shipping_postcode')
                        ->label('Postal Code'),

                    TextEntry::make('shipping_country')
                        ->label('Country'),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer.user.name')
                    ->label('Customer')
                    ->searchable(),

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

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => '$'.number_format($state / 100, 2))
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('invoice')
                    ->label('Invoice')
                    ->icon('heroicon-o-document-arrow-down')
                    ->visible(fn (Order $record) => auth()->user()?->can('invoice', $record))
                    ->action(fn (Order $record) => response()->streamDownload(
                        fn () => print (app(GenerateInvoicePdf::class)->execute($record)),
                        app(GenerateInvoicePdf::class)->filename($record),
                        ['Content-Type' => 'application/pdf'],
                    )),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('update_status')
                        ->label('Update Status')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Select::make('status')
                                ->options(collect(OrderStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $records->each(fn (Order $record) => $record->update(['status' => $data['status']]));
                        }),
                ]),
            ]);
    }

    public static function getRelationManagers(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['customer.user', 'shippingMethod']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
