<?php

namespace Minishop\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Minishop\Database\Factories\OrderReturnFactory;
use Minishop\Enums\ReturnReason;
use Minishop\Enums\ReturnStatus;

class OrderReturn extends Model
{
    /** @use HasFactory<OrderReturnFactory> */
    use HasFactory;

    protected $table = 'order_returns';

    protected $fillable = [
        'return_number',
        'order_id',
        'status',
        'reason',
        'notes',
        'admin_notes',
        'refund_amount',
        'stripe_refund_id',
        'restocked',
        'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReturnStatus::class,
            'reason' => ReturnReason::class,
            'refund_amount' => 'integer',
            'restocked' => 'boolean',
            'refunded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (OrderReturn $orderReturn): void {
            if (empty($orderReturn->return_number)) {
                $orderReturn->return_number = 'RMA-'.str_pad((string) $orderReturn->id, 6, '0', STR_PAD_LEFT);
                $orderReturn->saveQuietly();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'return_number';
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }
}
