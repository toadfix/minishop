<?php

namespace Minishop\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Minishop\Enums\TaxMode;

class StoreSettings extends Model
{
    protected $fillable = [
        'currency',
        'currency_locale',
        'tax_rate',
        'tax_mode',
        'gst_number',
        'active_payment_gateway',
        'paymongo_public_key',
        'paymongo_secret_key',
        'paymongo_webhook_secret',
        'low_stock_threshold',
        'sale_discount_percentage',
        'origin_postcode',
    ];

    protected function casts(): array
    {
        return [
            'paymongo_secret_key' => 'encrypted',
            'paymongo_webhook_secret' => 'encrypted',
            'tax_rate' => 'decimal:2',
            'low_stock_threshold' => 'integer',
            'sale_discount_percentage' => 'integer',
            'tax_mode' => TaxMode::class,
        ];
    }

    /**
     * Returns the current store settings, cached indefinitely.
     * Cache is invalidated by StoreSettingsObserver on save.
     */
    public static function current(): self
    {
        $settings = Cache::rememberForever('store_settings', fn () => static::firstOrCreate([]));

        // Guard against stale cached values (e.g. __PHP_Incomplete_Class after a deployment
        // that adds new casts or enums to this model).
        if (! $settings instanceof self) {
            Cache::forget('store_settings');
            $settings = Cache::rememberForever('store_settings', fn () => static::firstOrCreate([]));
        }

        return $settings;
    }
}
