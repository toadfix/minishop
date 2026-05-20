<?php

namespace Minishop\Data;

use Minishop\Models\Product;
use Minishop\Models\ProductVariant;

readonly class LowStockSubject
{
    public function __construct(
        public string $name,
        public int $stockQuantity,
        public ?string $sku,
        public string $productUrl,
    ) {}

    public static function fromProduct(Product $product): self
    {
        return new self(
            name: $product->name,
            stockQuantity: $product->stock_quantity,
            sku: $product->sku,
            productUrl: url(config('minishop.panel_path', 'dashboard').'/products/'.$product->id.'/edit'),
        );
    }

    public static function fromVariant(ProductVariant $variant): self
    {
        $sku = $variant->sku ?? 'N/A';

        return new self(
            name: "{$variant->product->name} (variant SKU: {$sku})",
            stockQuantity: $variant->stock_quantity,
            sku: $variant->sku,
            productUrl: url(config('minishop.panel_path', 'dashboard').'/products/'.$variant->product->id.'/edit'),
        );
    }
}
