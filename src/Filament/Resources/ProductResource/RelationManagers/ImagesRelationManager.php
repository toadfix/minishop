<?php

namespace Minishop\Filament\Resources\ProductResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Manage a product's images. Uploads are stored on the configured image disk
 * (config `minishop.image_disk`). An image can be product-level or tied to a
 * specific variant. Uses the `allImages` relation so both kinds are listed and
 * a chosen `variant_id` is preserved (the `images` relation hard-filters and
 * would scope inserts to product-level only).
 */
class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'allImages';

    protected static ?string $title = 'Images';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('path')
                ->label('Image')
                ->image()
                ->disk(config('minishop.image_disk'))
                ->directory('products')
                ->visibility('public')
                ->required()
                ->columnSpanFull(),

            TextInput::make('alt_text')
                ->label('Alt text')
                ->maxLength(255),

            Select::make('variant_id')
                ->label('Variant (optional)')
                ->options(fn () => $this->getOwnerRecord()->variants()->get()->pluck('sku', 'id'))
                ->placeholder('Product-level (no specific variant)'),

            TextInput::make('sort_order')
                ->numeric()
                ->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('path')
                    ->label('Preview')
                    ->disk(config('minishop.image_disk'))
                    ->height(60)
                    ->square(),

                TextColumn::make('alt_text')
                    ->placeholder('—'),

                TextColumn::make('variant.sku')
                    ->label('Variant')
                    ->placeholder('Product-level'),

                TextColumn::make('sort_order')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
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
