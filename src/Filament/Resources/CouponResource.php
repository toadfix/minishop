<?php

namespace Minishop\Filament\Resources;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Minishop\Enums\CouponType;
use Minishop\Filament\Resources\CouponResource\Pages;
use Minishop\Models\Coupon;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?int $navigationSort = 4;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-ticket';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Commerce';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),

            Textarea::make('description')
                ->columnSpanFull()
                ->nullable(),

            Select::make('type')
                ->options([
                    CouponType::Fixed->value => 'Fixed Amount',
                    CouponType::Percentage->value => 'Percentage',
                ])
                ->required()
                ->default(CouponType::Fixed->value),

            TextInput::make('value')
                ->label('Value ($ for fixed, % for percentage)')
                ->numeric()
                ->prefix('$')
                ->formatStateUsing(fn ($state, $record) => $record && $record->type === CouponType::Fixed ? $state / 100 : $state)
                ->dehydrateStateUsing(fn ($state, $record, $get) => $get('type') === CouponType::Fixed->value ? (int) ($state * 100) : (int) $state)
                ->required(),

            TextInput::make('minimum_order_amount')
                ->label('Minimum Order Amount')
                ->numeric()
                ->prefix('$')
                ->formatStateUsing(fn ($state) => $state ? $state / 100 : null)
                ->dehydrateStateUsing(fn ($state) => $state ? (int) ($state * 100) : null)
                ->nullable(),

            DatePicker::make('expiry_date')
                ->nullable(),

            TextInput::make('usage_limit')
                ->numeric()
                ->nullable(),

            Toggle::make('is_active')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (CouponType $state) => $state->label())
                    ->color(fn (CouponType $state) => match ($state) {
                        CouponType::Fixed => 'info',
                        CouponType::Percentage => 'warning',
                    }),

                TextColumn::make('value')
                    ->formatStateUsing(fn ($state, $record) => $record->type === CouponType::Fixed
                        ? '$'.number_format($state / 100, 2)
                        : $state.'%'
                    ),

                TextColumn::make('usage_limit')
                    ->label('Limit')
                    ->placeholder('Unlimited'),

                TextColumn::make('used_count')
                    ->label('Used'),

                TextColumn::make('expiry_date')
                    ->date()
                    ->placeholder('No expiry'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
