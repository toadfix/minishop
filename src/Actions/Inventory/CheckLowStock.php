<?php

namespace Minishop\Actions\Inventory;

use Illuminate\Support\Facades\Notification;
use Minishop\Data\LowStockSubject;
use Minishop\Models\Product;
use Minishop\Models\ProductVariant;
use Minishop\Models\StoreSettings;
use Minishop\Models\User;
use Minishop\Notifications\LowStockAlert;

class CheckLowStock
{
    public function execute(Product|ProductVariant $model): void
    {
        if ($model instanceof Product && $model->isBundled()) {
            return;
        }

        if (! $model->wasChanged('stock_quantity')) {
            return;
        }

        $threshold = $model->low_stock_threshold
            ?? StoreSettings::current()->low_stock_threshold;

        if ($threshold === null) {
            $this->setFlag($model, false);

            return;
        }

        if ($model->stock_quantity <= $threshold && ! $model->low_stock_notified) {
            $this->setFlag($model, true);

            $subject = $model instanceof ProductVariant
                ? LowStockSubject::fromVariant($model->loadMissing('product'))
                : LowStockSubject::fromProduct($model);

            Notification::send(
                User::role(['super-admin', 'admin'])
                    ->whereNotNull('email_verified_at')
                    ->get(),
                new LowStockAlert($subject),
            );
        }

        if ($model->stock_quantity > $threshold && $model->low_stock_notified) {
            $this->setFlag($model, false);
        }
    }

    /**
     * Persist the low_stock_notified flag via a direct query to bypass
     * Eloquent's fill/dirty tracking and avoid re-triggering observers.
     */
    private function setFlag(Product|ProductVariant $model, bool $notified): void
    {
        if ($model->low_stock_notified === $notified) {
            return;
        }

        $model->newQueryWithoutScopes()
            ->where($model->getKeyName(), $model->getKey())
            ->update(['low_stock_notified' => $notified]);
    }
}
