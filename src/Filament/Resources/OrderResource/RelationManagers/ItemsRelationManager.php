<?php

namespace Minishop\Filament\Resources\OrderResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Product'),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->placeholder('—'),

                TextColumn::make('quantity'),

                TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->formatStateUsing(fn ($state) => '$'.number_format($state / 100, 2)),

                TextColumn::make('total_price')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => '$'.number_format($state / 100, 2)),
            ])
            ->paginated(false);
    }
}
