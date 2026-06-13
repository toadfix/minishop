<?php

namespace Minishop\Filament\Resources;

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
use Minishop\Enums\ShippingMethodType;
use Minishop\Filament\Resources\ShippingMethodResource\Pages;
use Minishop\Models\ShippingMethod;

class ShippingMethodResource extends Resource
{
    protected static ?string $model = ShippingMethod::class;

    protected static ?int $navigationSort = 2;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-truck';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Configuration';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),

            Textarea::make('description')
                ->columnSpanFull()
                ->nullable(),

            Select::make('type')
                ->options([
                    ShippingMethodType::FlatRate->value => 'Flat Rate',
                    ShippingMethodType::Calculated->value => 'Calculated',
                ])
                ->required()
                ->default(ShippingMethodType::FlatRate->value),

            TextInput::make('price')
                ->label('Price')
                ->numeric()
                ->prefix('$')
                ->formatStateUsing(fn ($state) => $state ? $state / 100 : null)
                ->dehydrateStateUsing(fn ($state) => $state ? (int) ($state * 100) : null)
                ->nullable(),

            Toggle::make('is_free')
                ->label('Free Shipping')
                ->default(false),

            Toggle::make('is_active')
                ->default(true),

            TextInput::make('sort_order')
                ->numeric()
                ->default(0),

            TextInput::make('carrier')
                ->maxLength(255)
                ->nullable(),

            TextInput::make('service_code')
                ->maxLength(255)
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (ShippingMethodType $state) => match ($state) {
                        ShippingMethodType::FlatRate => 'Flat Rate',
                        ShippingMethodType::Calculated => 'Calculated',
                    })
                    ->color(fn (ShippingMethodType $state) => match ($state) {
                        ShippingMethodType::FlatRate => 'info',
                        ShippingMethodType::Calculated => 'warning',
                    }),

                TextColumn::make('price')
                    ->formatStateUsing(fn ($state) => '$'.number_format($state / 100, 2))
                    ->hidden(fn ($record) => $record?->is_free),

                IconColumn::make('is_free')
                    ->label('Free')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('sort_order')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
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
            'index' => Pages\ListShippingMethods::route('/'),
            'create' => Pages\CreateShippingMethod::route('/create'),
            'edit' => Pages\EditShippingMethod::route('/{record}/edit'),
        ];
    }
}
