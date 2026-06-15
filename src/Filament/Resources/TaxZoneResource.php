<?php

namespace Minishop\Filament\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Minishop\Filament\Resources\TaxZoneResource\Pages;
use Minishop\Filament\Resources\TaxZoneResource\RelationManagers\RatesRelationManager;
use Minishop\Models\TaxZone;

class TaxZoneResource extends Resource
{
    protected static ?string $model = TaxZone::class;

    protected static ?int $navigationSort = 3;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-calculator';
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

            TextInput::make('country_code')
                ->label('Country Code')
                ->required()
                ->maxLength(2)
                ->placeholder('CA'),

            TextInput::make('province_code')
                ->label('Province Code')
                ->maxLength(2)
                ->nullable()
                ->placeholder('ON'),

            TextInput::make('priority')
                ->numeric()
                ->default(0),

            Toggle::make('is_active')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('country_code')
                    ->label('Country'),

                TextColumn::make('province_code')
                    ->label('Province')
                    ->placeholder('—'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('priority')
                    ->sortable(),
            ])
            ->defaultSort('priority', 'desc')
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelationManagers(): array
    {
        return [
            RatesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxZones::route('/'),
            'create' => Pages\CreateTaxZone::route('/create'),
            'edit' => Pages\EditTaxZone::route('/{record}/edit'),
        ];
    }
}
