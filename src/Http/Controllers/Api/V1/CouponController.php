<?php

namespace Minishop\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Minishop\Http\Controllers\Controller;
use Minishop\Models\Coupon;

class CouponController extends Controller
{
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
            'subtotal' => ['required', 'integer', 'min:0'],
        ]);

        $coupon = Coupon::query()
            ->whereRaw('UPPER(code) = ?', [strtoupper($request->string('code')->toString())])
            ->first();

        if ($coupon === null) {
            return response()->json([
                'valid' => false,
                'message' => 'Coupon code not found.',
            ]);
        }

        $subtotal = (int) $request->integer('subtotal');

        if (! $coupon->isValid($subtotal)) {
            $message = match (true) {
                ! $coupon->is_active => 'This coupon is no longer active.',
                $coupon->expiry_date !== null && $coupon->expiry_date->isPast() => 'This coupon has expired.',
                $coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit => 'This coupon has reached its usage limit.',
                $coupon->minimum_order_amount !== null && $subtotal < $coupon->minimum_order_amount => 'Your order does not meet the minimum amount for this coupon.',
                default => 'This coupon is not valid.',
            };

            return response()->json([
                'valid' => false,
                'message' => $message,
            ]);
        }

        $discountAmount = $coupon->calculateDiscount($subtotal);

        return response()->json([
            'valid' => true,
            'discount_amount' => $discountAmount,
            'coupon' => [
                'code' => $coupon->code,
                'type' => $coupon->type->value,
                'value' => $coupon->value,
                'description' => $coupon->description,
            ],
        ]);
    }
}
