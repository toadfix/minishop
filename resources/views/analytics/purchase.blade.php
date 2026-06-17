@php($gaId = config('minishop.analytics.ga4_measurement_id'))
@if ($gaId && isset($order))
    @php($purchasePayload = [
        'transaction_id' => $order->order_number,
        'value' => round($order->total_amount / 100, 2),
        'currency' => \Minishop\Models\StoreSettings::current()->currency,
        'shipping' => round($order->shipping_amount / 100, 2),
        'tax' => round($order->tax_amount / 100, 2),
        'items' => $order->items->map(fn ($item) => [
            'item_id' => $item->product_sku,
            'item_name' => $item->product_name,
            'price' => round($item->unit_price / 100, 2),
            'quantity' => $item->quantity,
        ])->all(),
    ])
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('event', 'purchase', {!! json_encode($purchasePayload) !!});
    </script>
@endif
