<?php

namespace Minishop\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Minishop\Database\Factories\ShippingMethodFactory;
use Minishop\Enums\ShippingMethodType;

class ShippingMethod extends Model
{
    /** @use HasFactory<ShippingMethodFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'is_free',
        'is_active',
        'sort_order',
        'type',
        'carrier',
        'service_code',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'is_free' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'type' => ShippingMethodType::class,
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isCalculated(): bool
    {
        return $this->type === ShippingMethodType::Calculated;
    }

    public function isFlatRate(): bool
    {
        return $this->type === ShippingMethodType::FlatRate;
    }

    public function getEffectivePriceAttribute(): int
    {
        return $this->is_free ? 0 : $this->price;
    }
}
