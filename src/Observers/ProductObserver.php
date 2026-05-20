<?php

namespace Minishop\Observers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Minishop\Actions\Inventory\CheckLowStock;
use Minishop\Models\ActivityLog;
use Minishop\Models\Product;

class ProductObserver
{
    public function created(Product $product): void
    {
        ActivityLog::query()->create([
            'user_id' => Auth::id(),
            'action' => 'created',
            'subject_type' => 'Product',
            'subject_id' => $product->id,
            'description' => "Created product \"{$product->name}\"",
            'properties' => null,
        ]);
    }

    public function updated(Product $product): void
    {
        $changed = $product->getChanges();
        unset($changed['updated_at']);

        if (empty($changed)) {
            return;
        }

        ActivityLog::query()->create([
            'user_id' => Auth::id(),
            'action' => 'updated',
            'subject_type' => 'Product',
            'subject_id' => $product->id,
            'description' => "Updated product \"{$product->name}\"",
            'properties' => $changed,
        ]);

        app(CheckLowStock::class)->execute($product);
    }

    public function deleting(Product $product): void
    {
        foreach ($product->allImages as $image) {
            Storage::disk('public')->delete($image->path);
        }
    }

    public function deleted(Product $product): void
    {
        ActivityLog::query()->create([
            'user_id' => Auth::id(),
            'action' => 'deleted',
            'subject_type' => 'Product',
            'subject_id' => $product->id,
            'description' => "Deleted product \"{$product->name}\"",
            'properties' => null,
        ]);
    }
}
