<?php

namespace Minishop\Filament\Resources\ProductResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Minishop\Models\Product;
use Minishop\Models\ProductOptionValue;

/**
 * Manage a variable product's variants: SKU, price, stock and the option
 * values (e.g. "Size: M") that distinguish each variant. Only the owning
 * product's option values are selectable.
 */
class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Variants';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Product && $ownerRecord->isVariable();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('sku')
                ->label('SKU')
                ->maxLength(255),

            TextInput::make('price')
                ->label('Price')
                ->numeric()
                ->prefix('$')
                ->required()
                ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                ->dehydrateStateUsing(fn ($state) => $state !== null ? (int) round($state * 100) : null),

            TextInput::make('stock_quantity')
                ->label('Stock')
                ->numeric()
                ->default(0)
                ->required(),

            TextInput::make('low_stock_threshold')
                ->numeric()
                ->nullable(),

            TextInput::make('weight_grams')
                ->label('Weight (g)')
                ->numeric()
                ->nullable(),

            Toggle::make('is_active')
                ->default(true),

            CheckboxList::make('optionValues')
                ->label('Option values')
                ->relationship(
                    name: 'optionValues',
                    titleAttribute: 'value',
                    modifyQueryUsing: fn (Builder $query) => $query->whereHas(
                        'option',
                        fn (Builder $q) => $q->where('product_id', $this->getOwnerRecord()->getKey()),
                    ),
                )
                ->getOptionLabelFromRecordUsing(fn (ProductOptionValue $record) => $record->option
                    ? "{$record->option->name}: {$record->value}"
                    : $record->value)
                ->columns(2)
                ->columnSpanFull()
                ->helperText('Add options to this product first to choose values here.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sku')
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('optionValues.value')
                    ->label('Options')
                    ->badge()
                    ->formatStateUsing(fn ($state, ProductOptionValue $record) => $record->option
                        ? "{$record->option->name}: {$record->value}"
                        : $record->value),

                TextColumn::make('price')
                    ->money('usd', divideBy: 100)
                    ->sortable(),

                TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
