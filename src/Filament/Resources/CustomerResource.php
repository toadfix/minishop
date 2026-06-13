<?php

namespace Minishop\Filament\Resources;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Minishop\Filament\Resources\CustomerResource\Pages;
use Minishop\Models\Customer;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?int $navigationSort = 3;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-user-group';
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
            Section::make('Customer Details')
                ->schema([
                    TextEntry::make('user.name')
                        ->label('Name'),

                    TextEntry::make('user.email')
                        ->label('Email'),

                    TextEntry::make('phone')
                        ->placeholder('—'),

                    TextEntry::make('created_at')
                        ->dateTime()
                        ->label('Joined'),
                ]),

            Section::make('Orders')
                ->schema([
                    RepeatableEntry::make('orders')
                        ->schema([
                            TextEntry::make('order_number')
                                ->label('Order #'),

                            TextEntry::make('status')
                                ->badge()
                                ->formatStateUsing(fn ($state) => $state->label()),

                            TextEntry::make('total_amount')
                                ->formatStateUsing(fn ($state) => '$'.number_format($state / 100, 2))
                                ->label('Total'),

                            TextEntry::make('created_at')
                                ->dateTime()
                                ->label('Date'),
                        ])
                        ->columns(4),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),

                TextColumn::make('orders_count')
                    ->counts('orders')
                    ->label('Orders'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                ViewAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'view' => Pages\ViewCustomer::route('/{record}'),
        ];
    }
}
