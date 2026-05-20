<?php

namespace Minishop\Filament\Resources\TaxZoneResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RatesRelationManager extends RelationManager
{
    protected static string $relationship = 'rates';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),

            TextInput::make('name_fr')
                ->label('Name (French)')
                ->maxLength(255)
                ->nullable(),

            TextInput::make('rate')
                ->required()
                ->numeric()
                ->suffix('%')
                ->step(0.0001),

            TextInput::make('sort_order')
                ->numeric()
                ->default(0),

            Toggle::make('is_compound')
                ->label('Compound Tax')
                ->default(false),

            Toggle::make('is_shipping_taxable')
                ->label('Apply to Shipping')
                ->default(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),

                TextColumn::make('rate')
                    ->formatStateUsing(fn ($state) => $state.'%'),

                IconColumn::make('is_compound')
                    ->label('Compound')
                    ->boolean(),

                IconColumn::make('is_shipping_taxable')
                    ->label('On Shipping')
                    ->boolean(),

                TextColumn::make('sort_order')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}
