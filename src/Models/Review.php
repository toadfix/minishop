<?php

namespace Minishop\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Minishop\Database\Factories\ReviewFactory;
use Minishop\Enums\ReviewStatus;

class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'rating',
        'title',
        'body',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'status' => ReviewStatus::class,
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<Review>  $query
     */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', ReviewStatus::Approved);
    }

    /**
     * A user may review a product only if they have a paid order containing it
     * and have not already reviewed it (one review per customer per product).
     */
    public static function userMayReview(User $user, Product $product): bool
    {
        $alreadyReviewed = static::query()
            ->where('product_id', $product->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyReviewed) {
            return false;
        }

        return OrderItem::query()
            ->where('product_id', $product->id)
            ->whereHas('order', fn (Builder $q) => $q
                ->where('payment_status', 'paid')
                ->whereHas('customer', fn (Builder $c) => $c->where('user_id', $user->id)))
            ->exists();
    }
}
