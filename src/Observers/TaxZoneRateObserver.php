<?php

namespace Minishop\Observers;

use Illuminate\Support\Facades\Cache;
use Minishop\Models\TaxZoneRate;

class TaxZoneRateObserver
{
    /**
     * Flush all cached tax zone lookups when a rate is saved or deleted.
     * Uses tagged cache so all zone entries are invalidated atomically.
     */
    public function saved(TaxZoneRate $taxZoneRate): void
    {
        Cache::forget('tax_zones_version');
    }

    public function deleted(TaxZoneRate $taxZoneRate): void
    {
        Cache::forget('tax_zones_version');
    }
}
