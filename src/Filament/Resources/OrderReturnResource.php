<?php

namespace Minishop\Filament\Resources;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Minishop\Enums\ReturnStatus;
use Minishop\Filament\Resources\OrderReturnResource\Pages;
use Minishop\Models\OrderReturn;

class OrderReturnResource extends Resource
{
    protected static ?string $model = OrderReturn::class;

    protected static ?int $navigationSort = 2;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-arrow-uturn-left';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Commerce';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Return Details')
                ->schema([
                    TextEntry::make('return_number')
                        ->label('Return #'),

                    TextEntry::make('order.order_number')
                        ->label('Order #'),

                    TextEntry::make('status')
                        ->badge()
                        ->formatStateUsing(fn (ReturnStatus $state) => $state->label())
                        ->color(fn (ReturnStatus $state) => match ($state) {
                            ReturnStatus::Requested => 'warning',
                            ReturnStatus::Approved => 'info',
                            ReturnStatus::Rejected => 'danger',
                            ReturnStatus::Received => 'primary',
                            ReturnStatus::Refunded => 'success',
                        }),

                    TextEntry::make('reason')
                        ->formatStateUsing(fn ($state) => $state?->label() ?? '—'),

                    TextEntry::make('notes')
                        ->columnSpanFull()
                        ->placeholder('—'),

                    TextEntry::make('admin_notes')
                        ->label('Admin Notes')
                        ->columnSpanFull()
                        ->placeholder('—'),

                    TextEntry::make('refund_amount')
                        ->label('Refund Amount')
                        ->formatStateUsing(fn ($state) => $state ? '$'.number_format($state / 100, 2) : '—'),

                    TextEntry::make('refunded_at')
                        ->label('Refunded At')
                        ->dateTime()
                        ->placeholder('—'),

                    TextEntry::make('created_at')
                        ->dateTime()
                        ->label('Submitted'),
                ])
                ->columns(2),

            Section::make('Return Items')
                ->schema([
                    RepeatableEntry::make('items')
                        ->schema([
                            TextEntry::make('product_name')
                                ->label('Product'),

                            TextEntry::make('quantity'),

                            TextEntry::make('unit_price')
                                ->label('Unit Price')
                                ->formatStateUsing(fn ($state) => $state ? '$'.number_format($state / 100, 2) : '—'),
                        ])
                        ->columns(3),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('return_number')
                    ->label('Return #')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->searchable(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (ReturnStatus $state) => $state->label())
                    ->color(fn (ReturnStatus $state) => match ($state) {
                        ReturnStatus::Requested => 'warning',
                        ReturnStatus::Approved => 'info',
                        ReturnStatus::Rejected => 'danger',
                        ReturnStatus::Received => 'primary',
                        ReturnStatus::Refunded => 'success',
                    }),

                TextColumn::make('reason')
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '—'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(ReturnStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])),
            ])
            ->actions([
                ViewAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['order']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrderReturns::route('/'),
            'view' => Pages\ViewOrderReturn::route('/{record}'),
        ];
    }
}
