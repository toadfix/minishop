<?php

namespace Minishop\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Minishop\Database\Factories\CouponFactory;
use Minishop\Enums\CouponType;

class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'type',
        'value',
        'minimum_order_amount',
        'expiry_date',
        'usage_limit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'expiry_date' => 'date',
            'value' => 'integer',
            'minimum_order_amount' => 'integer',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isValid(int $subtotal): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expiry_date !== null && $this->expiry_date->lt(Carbon::today())) {
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        if ($this->minimum_order_amount !== null && $subtotal < $this->minimum_order_amount) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(int $subtotal): int
    {
        return match ($this->type) {
            CouponType::Fixed => min($this->value, $subtotal),
            CouponType::Percentage => (int) round($subtotal * $this->value / 100),
        };
    }
}
