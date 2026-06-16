<?php

namespace Minishop\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Minishop\Enums\OrderStatus;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_id',
        'coupon_id',
        'shipping_method_id',
        'carrier',
        'tracking_number',
        'status',
        'payment_gateway',
        'payment_intent_id',
        'payment_status',
        'paid_at',
        'shipped_at',
        'delivered_at',
        'subtotal',
        'discount_amount',
        'shipping_amount',
        'tax_amount',
        'total_amount',
        'refunded_amount',
        'refunded_at',
        'shipping_name',
        'shipping_address_line1',
        'shipping_address_line2',
        'shipping_city',
        'shipping_state',
        'shipping_postcode',
        'shipping_country',
        'notes',
        'tax_zone_name',
        'tax_breakdown',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'integer',
            'discount_amount' => 'integer',
            'shipping_amount' => 'integer',
            'tax_amount' => 'integer',
            'total_amount' => 'integer',
            'refunded_amount' => 'integer',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'tax_breakdown' => 'array',
        ];
    }

    /**
     * The amount still eligible to be refunded (order total minus what has
     * already been refunded, across both the returns flow and direct refunds).
     */
    public function refundableAmount(): int
    {
        return max(0, $this->total_amount - $this->refunded_amount);
    }

    protected static function booted(): void
    {
        static::created(function (Order $order): void {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT);
                $order->saveQuietly();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(OrderReturn::class);
    }
}
