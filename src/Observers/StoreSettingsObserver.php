<?php

namespace Minishop\Observers;

use Illuminate\Support\Facades\Cache;
use Minishop\Models\StoreSettings;

class StoreSettingsObserver
{
    /**
     * Flush the cached StoreSettings when the record is saved.
     */
    public function saved(StoreSettings $storeSettings): void
    {
        Cache::forget('store_settings');
    }
}
