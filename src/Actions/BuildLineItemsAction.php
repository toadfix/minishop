<?php

namespace Minishop\Actions;

use Illuminate\Support\Collection;
use Minishop\Models\Product;
use Minishop\Models\ProductVariant;
use Minishop\Models\StoreSettings;

class BuildLineItemsAction
{
    /**
     * Build resolved line items from raw cart items, handling all product types.
     *
     * @param  array<int, array{product_id: int, variant_id: int|null, quantity: int}>  $items
     * @return array<int, array{product_id: int, variant_id: int|null, product_name: string, product_sku: string|null, unit_price: int, quantity: int, subtotal: int}>
     */
    public function execute(array $items): array
    {
        $productIds = collect($items)->pluck('product_id')->unique()->values()->all();

        /** @var Collection<int, Product> $products */
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->with('bundleItems.componentProduct', 'bundleItems.componentVariant')
            ->get()
            ->keyBy('id');

        $saleDiscount = (int) (StoreSettings::current()->sale_discount_percentage ?? 0);
        $lineItems = [];

        foreach ($items as $item) {
            $product = $products[$item['product_id']];
            abort_unless($product->is_active, 422, 'One or more products are no longer available.');

            if ($product->isBundled()) {
                $lineItems[] = $this->buildBundledLineItem($product, $item['quantity'], $saleDiscount);
            } elseif (! empty($item['variant_id'])) {
                $lineItems[] = $this->buildVariantLineItem($product, $item['variant_id'], $item['quantity'], $saleDiscount);
            } else {
                $lineItems[] = $this->buildSimpleLineItem($product, $item['quantity'], $saleDiscount);
            }
        }

        return $lineItems;
    }

    /**
     * @return array{product_id: int, variant_id: int|null, product_name: string, product_sku: string|null, unit_price: int, quantity: int, subtotal: int}
     */
    private function buildSimpleLineItem(Product $product, int $quantity, int $saleDiscount): array
    {
        abort_if($product->stock_quantity < $quantity, 422, "Insufficient stock for {$product->name}.");

        $unitPrice = $this->applyOnSaleDiscount($product->price, $product, $saleDiscount);

        return [
            'product_id' => $product->id,
            'variant_id' => null,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'subtotal' => $unitPrice * $quantity,
        ];
    }

    /**
     * @return array{product_id: int, variant_id: int|null, product_name: string, product_sku: string|null, unit_price: int, quantity: int, subtotal: int}
     */
    private function buildVariantLineItem(Product $product, int $variantId, int $quantity, int $saleDiscount): array
    {
        $variant = ProductVariant::query()
            ->where('id', $variantId)
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->firstOrFail();

        abort_if($variant->stock_quantity < $quantity, 422, "Insufficient stock for {$product->name}.");

        $basePrice = $variant->price ?? $product->price;
        $unitPrice = $this->applyOnSaleDiscount($basePrice, $product, $saleDiscount);
        $sku = $variant->sku ?? $product->sku;

        return [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'product_name' => $product->name,
            'product_sku' => $sku,
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'subtotal' => $unitPrice * $quantity,
        ];
    }

    /**
     * @return array{product_id: int, variant_id: int|null, product_name: string, product_sku: string|null, unit_price: int, quantity: int, subtotal: int}
     */
    private function buildBundledLineItem(Product $product, int $quantity, int $saleDiscount): array
    {
        $effectiveStock = $product->getEffectiveStock();

        abort_if($effectiveStock < $quantity, 422, "Insufficient stock for {$product->name}.");

        $unitPrice = $this->applyOnSaleDiscount($product->price, $product, $saleDiscount);

        return [
            'product_id' => $product->id,
            'variant_id' => null,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'subtotal' => $unitPrice * $quantity,
        ];
    }

    private function applyOnSaleDiscount(int $basePrice, Product $product, int $saleDiscount): int
    {
        if (! $product->on_sale || $saleDiscount <= 0) {
            return $basePrice;
        }

        return (int) round($basePrice * (1 - $saleDiscount / 100));
    }
}
