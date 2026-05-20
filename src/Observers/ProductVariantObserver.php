<?php

namespace Minishop\Observers;

use Minishop\Actions\Inventory\CheckLowStock;
use Minishop\Models\ProductVariant;

class ProductVariantObserver
{
    public function updated(ProductVariant $variant): void
    {
        app(CheckLowStock::class)->execute($variant);
    }
}
