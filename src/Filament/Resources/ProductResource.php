<?php

namespace Minishop\Filament\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Minishop\Enums\ProductType;
use Minishop\Filament\Resources\ProductResource\Pages;
use Minishop\Filament\Resources\ProductResource\RelationManagers;
use Minishop\Models\Product;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-archive-box';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Catalog';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')
                ->options([
                    ProductType::Simple->value => 'Simple',
                    ProductType::Variable->value => 'Variable',
                    ProductType::Bundled->value => 'Bundled',
                ])
                ->required()
                ->default(ProductType::Simple->value)
                ->disabled(fn (string $operation) => $operation === 'edit')
                ->dehydrated(),

            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function (string $operation, $state, Set $set) {
                    if ($operation === 'create') {
                        $set('slug', Str::slug($state));
                    }
                }),

            TextInput::make('slug')
                ->required()
                ->maxLength(255)
                ->disabled(fn (string $operation) => $operation === 'edit')
                ->dehydrated(),

            Textarea::make('description')
                ->columnSpanFull()
                ->nullable(),

            TextInput::make('price')
                ->label('Price')
                ->numeric()
                ->prefix('$')
                ->formatStateUsing(fn ($state) => $state ? $state / 100 : null)
                ->dehydrateStateUsing(fn ($state) => $state ? (int) ($state * 100) : null)
                ->required(),

            TextInput::make('compare_price')
                ->label('Compare At Price')
                ->numeric()
                ->prefix('$')
                ->formatStateUsing(fn ($state) => $state ? $state / 100 : null)
                ->dehydrateStateUsing(fn ($state) => $state ? (int) ($state * 100) : null)
                ->nullable(),

            TextInput::make('sku')
                ->label('SKU')
                ->maxLength(255)
                ->nullable(),

            TextInput::make('stock_quantity')
                ->label('Stock')
                ->numeric()
                ->default(0)
                ->visible(fn (string $operation, $record) => $operation === 'create' || ($record && $record->isSimple())),

            Toggle::make('is_active')
                ->default(true),

            Toggle::make('on_sale')
                ->label('On Sale'),

            Toggle::make('is_featured')
                ->label('Featured'),

            TextInput::make('meta_title')
                ->label('SEO Title')
                ->maxLength(255)
                ->nullable(),

            Textarea::make('meta_description')
                ->label('SEO Description')
                ->columnSpanFull()
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
                    ->formatStateUsing(fn (ProductType $state) => match ($state) {
                        ProductType::Simple => 'Simple',
                        ProductType::Variable => 'Variable',
                        ProductType::Bundled => 'Bundled',
                    })
                    ->color(fn (ProductType $state) => match ($state) {
                        ProductType::Simple => 'gray',
                        ProductType::Variable => 'info',
                        ProductType::Bundled => 'warning',
                    }),

                TextColumn::make('price')
                    ->formatStateUsing(fn ($state) => '$'.number_format($state / 100, 2))
                    ->sortable(),

                TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                IconColumn::make('on_sale')
                    ->label('Sale')
                    ->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        ProductType::Simple->value => 'Simple',
                        ProductType::Variable->value => 'Variable',
                        ProductType::Bundled->value => 'Bundled',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Active'),
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

    public static function getRelations(): array
    {
        return [
            RelationManagers\OptionsRelationManager::class,
            RelationManagers\VariantsRelationManager::class,
            RelationManagers\ImagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
