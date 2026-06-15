@extends('minishop::storefront.layout')

@section('title', 'Order '.$order->order_number)

@section('content')
    <nav class="mb-6 text-sm text-gray-500">
        <a href="{{ route('account.orders.index') }}" class="hover:text-brand-600">Orders</a>
        <span class="px-2">/</span>
        <span class="text-gray-700">{{ $order->order_number }}</span>
    </nav>

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ $order->order_number }}</h1>
        <span class="rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-700">{{ ucfirst($order->status->value ?? $order->status) }}</span>
    </div>
    <p class="mt-1 text-sm text-gray-500">Placed {{ $order->created_at->format('M j, Y') }}</p>

    <div class="mt-8 grid gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2 rounded-lg border border-gray-200 bg-white p-6">
            <ul class="divide-y divide-gray-100 text-sm">
                @foreach ($order->items as $item)
                    <li class="flex justify-between gap-2 py-3">
                        <span class="text-gray-700">
                            {{ $item->quantity }} &times; {{ $item->product_name }}
                            @if ($item->variant)
                                <span class="text-gray-400">({{ $item->variant->optionValues->pluck('value')->join(' / ') }})</span>
                            @endif
                        </span>
                        <span class="text-gray-900">{{ \Minishop\Support\Money::format($item->unit_price * $item->quantity) }}</span>
                    </li>
                @endforeach
            </ul>

            <dl class="mt-4 space-y-1 border-t border-gray-200 pt-4 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Subtotal</dt><dd>{{ \Minishop\Support\Money::format($order->subtotal) }}</dd></div>
                @if ($order->discount_amount)
                    <div class="flex justify-between"><dt class="text-gray-500">Discount</dt><dd class="text-green-700">&minus;{{ \Minishop\Support\Money::format($order->discount_amount) }}</dd></div>
                @endif
                <div class="flex justify-between"><dt class="text-gray-500">Shipping</dt><dd>{{ \Minishop\Support\Money::format($order->shipping_amount) }}</dd></div>
                @if ($order->tax_amount)
                    <div class="flex justify-between"><dt class="text-gray-500">Tax</dt><dd>{{ \Minishop\Support\Money::format($order->tax_amount) }}</dd></div>
                @endif
                <div class="flex justify-between border-t border-gray-200 pt-2 text-base font-semibold"><dt>Total</dt><dd>{{ \Minishop\Support\Money::format($order->total_amount) }}</dd></div>
            </dl>
        </div>

        <aside class="h-fit space-y-6 rounded-lg border border-gray-200 bg-white p-6 text-sm text-gray-600">
            <div>
                <h2 class="font-semibold text-gray-900">Shipping to</h2>
                <address class="mt-2 not-italic leading-relaxed">
                    {{ $order->shipping_name }}<br>
                    {{ $order->shipping_address_line1 }}<br>
                    @if ($order->shipping_address_line2){{ $order->shipping_address_line2 }}<br>@endif
                    {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_postcode }}<br>
                    {{ $order->shipping_country }}
                </address>
            </div>

            @if ($order->tracking_number || $order->shipped_at)
                <div class="border-t border-gray-100 pt-6">
                    <h2 class="font-semibold text-gray-900">Shipment</h2>
                    <dl class="mt-2 space-y-1">
                        @if ($order->carrier)
                            <div class="flex justify-between gap-2"><dt class="text-gray-500">Carrier</dt><dd class="text-gray-900">{{ $order->carrier }}</dd></div>
                        @endif
                        @if ($order->tracking_number)
                            <div class="flex justify-between gap-2"><dt class="text-gray-500">Tracking #</dt><dd class="font-medium text-gray-900">{{ $order->tracking_number }}</dd></div>
                        @endif
                        @if ($order->shipped_at)
                            <div class="flex justify-between gap-2"><dt class="text-gray-500">Shipped</dt><dd class="text-gray-900">{{ $order->shipped_at->format('M j, Y') }}</dd></div>
                        @endif
                        @if ($order->delivered_at)
                            <div class="flex justify-between gap-2"><dt class="text-gray-500">Delivered</dt><dd class="text-gray-900">{{ $order->delivered_at->format('M j, Y') }}</dd></div>
                        @endif
                    </dl>
                </div>
            @endif
        </aside>
    </div>
@endsection
