<?php

namespace Minishop\Filament\Resources\ProductResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Minishop\Models\Product;

/**
 * Manage a variable product's options (e.g. "Size") and their values
 * (e.g. "S", "M", "L"). Values are edited inline via a repeater on the
 * `values` has-many relationship.
 */
class OptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'options';

    protected static ?string $title = 'Options';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Product && $ownerRecord->isVariable();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->helperText('e.g. Size, Colour'),

            TextInput::make('position')
                ->numeric()
                ->default(0),

            Repeater::make('values')
                ->relationship('values')
                ->schema([
                    TextInput::make('value')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('position')
                        ->numeric()
                        ->default(0),
                ])
                ->orderColumn('position')
                ->columns(2)
                ->columnSpanFull()
                ->addActionLabel('Add value'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),

                TextColumn::make('values_count')
                    ->label('Values')
                    ->counts('values'),

                TextColumn::make('position')
                    ->sortable(),
            ])
            ->defaultSort('position')
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
