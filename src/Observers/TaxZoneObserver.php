<?php

namespace Minishop\Observers;

use Illuminate\Support\Facades\Cache;
use Minishop\Models\TaxZone;

class TaxZoneObserver
{
    /**
     * Flush all cached tax zone lookups when a zone is saved or deleted.
     * Uses tagged cache so all zone entries are invalidated atomically.
     */
    public function saved(TaxZone $taxZone): void
    {
        Cache::forget('tax_zones_version');
    }

    public function deleted(TaxZone $taxZone): void
    {
        Cache::forget('tax_zones_version');
    }
}
