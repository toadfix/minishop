<?php

namespace Minishop\Observers;

use Illuminate\Support\Facades\Storage;
use Minishop\Models\ProductImage;

class ProductImageObserver
{
    /**
     * Remove the underlying file when an image record is deleted on its own
     * (e.g. via the admin Images relation manager). Product-level deletion is
     * handled by ProductObserver, because the product_images foreign key
     * cascades at the database level and does not fire model events.
     */
    public function deleting(ProductImage $image): void
    {
        if (blank($image->path)) {
            return;
        }

        Storage::disk(config('minishop.image_disk'))->delete($image->path);
    }
}
