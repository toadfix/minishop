<?php

namespace Minishop\Actions;

use Illuminate\Support\Facades\DB;
use Minishop\Actions\Inventory\DecrementStockAction;
use Minishop\Enums\ShippingMethodType;
use Minishop\Models\Coupon;
use Minishop\Models\Order;
use Minishop\Models\ShippingMethod;

class CreateOrderAction
{
    /**
     * Create an order with its line items, applying coupon discounts, shipping, and tax.
     *
     * Callers are responsible for resolving each item's product_name, product_sku,
     * unit_price, and subtotal before calling this action.
     *
     * @param  array{
     *     customer_id: int,
     *     status: string,
     *     payment_status: string,
     *     payment_gateway: string|null,
     *     items: array<int, array{
     *         product_id: int,
     *         variant_id: int|null,
     *         product_name: string,
     *         product_sku: string|null,
     *         unit_price: int,
     *         quantity: int,
     *         subtotal: int,
     *     }>,
     *     coupon_code: string|null,
     *     shipping_method_id: int|null,
     *     carrier: string|null,
     *     service_code: string|null,
     *     session_id: string|null,
     *     shipping_name: string,
     *     shipping_address_line1: string,
     *     shipping_address_line2: string|null,
     *     shipping_city: string,
     *     shipping_state: string,
     *     shipping_postcode: string,
     *     shipping_country: string,
     *     notes: string|null,
     * } $data
     */
    public function __construct(
        private readonly ResolveTaxAction $resolveTax,
        private readonly DecrementStockAction $decrementStock,
    ) {}

    public function execute(array $data): Order
    {
        $subtotal = collect($data['items'])->sum('subtotal');

        $coupon = null;
        $discountAmount = 0;

        if (! empty($data['coupon_code'])) {
            $coupon = Coupon::query()
                ->whereRaw('UPPER(code) = ?', [strtoupper($data['coupon_code'])])
                ->first();

            if ($coupon?->isValid($subtotal)) {
                $discountAmount = $coupon->calculateDiscount($subtotal);
            }
        }

        $taxableAmount = max(0, $subtotal - $discountAmount);

        // Resolve tax BEFORE opening the DB transaction to minimize the lock window.
        // tax_breakdown is always sourced from the resolution — never from client data.
        // Province code must be exactly 2 alpha chars (e.g. "ON"), matching the format
        // stored in tax_zones.province_code. Free-text state values (e.g. "Ontario") are
        // treated as no province, falling through to the country catch-all or global zone.
        $rawState = strtoupper(trim($data['shipping_state'] ?? ''));
        $provinceCode = strlen($rawState) === 2 ? $rawState : null;

        $resolution = $this->resolveTax->execute(
            $data['shipping_country'],
            $provinceCode,
            $taxableAmount
        );
        $taxAmount = $resolution->totalTaxCents;

        return DB::transaction(function () use ($data, $subtotal, $coupon, $discountAmount, $taxableAmount, $taxAmount, $resolution): Order {
            $shippingMethod = isset($data['shipping_method_id'])
                ? ShippingMethod::query()->find($data['shipping_method_id'])
                : null;

            $shippingAmount = $this->resolveShippingAmount($shippingMethod, $data);

            $totalAmount = max(0, $taxableAmount + $shippingAmount + $taxAmount);

            $order = Order::query()->create([
                'order_number' => '',
                'customer_id' => $data['customer_id'],
                'coupon_id' => $coupon?->id,
                'shipping_method_id' => $data['shipping_method_id'] ?? null,
                'status' => $data['status'],
                'payment_gateway' => $data['payment_gateway'] ?? null,
                'payment_status' => $data['payment_status'],
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'shipping_amount' => $shippingAmount,
                'tax_amount' => $taxAmount,
                'tax_zone_name' => $resolution->zoneName,
                'tax_breakdown' => $resolution->breakdown ?: null,
                'total_amount' => $totalAmount,
                'shipping_name' => $data['shipping_name'],
                'shipping_address_line1' => $data['shipping_address_line1'],
                'shipping_address_line2' => $data['shipping_address_line2'] ?? null,
                'shipping_city' => $data['shipping_city'],
                'shipping_state' => $data['shipping_state'],
                'shipping_postcode' => $data['shipping_postcode'],
                'shipping_country' => $data['shipping_country'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'product_sku' => $item['product_sku'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            $this->decrementStock->execute($data['items']);

            $coupon?->increment('used_count');

            return $order;
        });
    }

    /**
     * Resolves the shipping amount server-side.
     * For calculated methods, looks up the amount from the session-cached quotes
     * rather than trusting any client-supplied value.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveShippingAmount(?ShippingMethod $shippingMethod, array $data): int
    {
        if (! $shippingMethod) {
            return 0;
        }

        if ($shippingMethod->is_free) {
            return 0;
        }

        if ($shippingMethod->type === ShippingMethodType::Calculated) {
            $carrier = $data['carrier'] ?? null;
            $serviceCode = $data['service_code'] ?? null;
            $sessionQuotes = $data['session_quotes'] ?? [];

            if ($carrier && $serviceCode && ! empty($sessionQuotes)) {
                $quote = collect($sessionQuotes)->first(
                    fn ($q) => $q['carrier'] === $carrier && $q['service_code'] === $serviceCode
                );

                if ($quote) {
                    return (int) $quote['amount_cents'];
                }
            }

            return 0;
        }

        return $shippingMethod->price;
    }
}
