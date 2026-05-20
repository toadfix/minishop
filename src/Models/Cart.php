<?php

namespace Minishop\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Minishop\Database\Factories\CartFactory;

class Cart extends Model
{
    /** @use HasFactory<CartFactory> */
    use HasFactory;

    protected $fillable = [
        'session_id',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public static function resolveOrCreate(Request $request): self
    {
        if ($request->user()) {
            $userCart = self::query()->firstOrCreate(['user_id' => $request->user()->id]);

            // Merge guest cart if a cart token cookie is present
            $token = $request->cookie('cart_token');

            if ($token) {
                $guestCart = self::query()
                    ->where('session_id', $token)
                    ->whereNull('user_id')
                    ->first();

                if ($guestCart && $guestCart->id !== $userCart->id) {
                    $userCart->load('items');
                    $userItemsByKey = $userCart->items->keyBy(
                        fn ($item) => $item->product_id.'-'.($item->variant_id ?? 'null')
                    );

                    foreach ($guestCart->items as $guestItem) {
                        $key = $guestItem->product_id.'-'.($guestItem->variant_id ?? 'null');
                        $existing = $userItemsByKey->get($key);

                        if ($existing) {
                            $existing->increment('quantity', $guestItem->quantity);
                        } else {
                            $userCart->items()->create([
                                'product_id' => $guestItem->product_id,
                                'variant_id' => $guestItem->variant_id,
                                'quantity' => $guestItem->quantity,
                                'unit_price' => $guestItem->unit_price,
                            ]);
                        }
                    }

                    $guestCart->delete();
                }
            }

            return $userCart;
        }

        $token = $request->cookie('cart_token');

        if ($token) {
            $cart = self::query()
                ->where('session_id', $token)
                ->whereNull('user_id')
                ->first();

            if ($cart) {
                return $cart;
            }
        }

        $newToken = Str::uuid()->toString();
        Cookie::queue('cart_token', $newToken, 60 * 24 * 30);

        return self::create(['session_id' => $newToken]);
    }
}
