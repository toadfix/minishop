@extends('minishop::storefront.layout')

@section('title', 'Order confirmed')

@section('content')
    <div class="mx-auto max-w-2xl text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
        </div>
        <h1 class="mt-4 text-2xl font-bold tracking-tight text-gray-900">Thank you for your order</h1>
        <p class="mt-2 text-gray-500">Your order <span class="font-medium text-gray-900">{{ $order->order_number }}</span> has been received.</p>
    </div>

    <div class="mx-auto mt-10 max-w-2xl rounded-lg border border-gray-200 bg-white p-6">
        <ul class="divide-y divide-gray-100 text-sm">
            @foreach ($order->items as $item)
                <li class="flex justify-between gap-2 py-3">
                    <span class="text-gray-700">{{ $item->quantity }} &times; {{ $item->product_name }}</span>
                    <span class="text-gray-900">{{ \Minishop\Support\Money::format($item->unit_price * $item->quantity) }}</span>
                </li>
            @endforeach
        </ul>

        <dl class="mt-4 space-y-1 border-t border-gray-200 pt-4 text-sm">
            <div class="flex justify-between"><dt class="text-gray-500">Subtotal</dt><dd class="text-gray-900">{{ \Minishop\Support\Money::format($order->subtotal) }}</dd></div>
            @if ($order->discount_amount)
                <div class="flex justify-between"><dt class="text-gray-500">Discount</dt><dd class="text-green-700">&minus;{{ \Minishop\Support\Money::format($order->discount_amount) }}</dd></div>
            @endif
            <div class="flex justify-between"><dt class="text-gray-500">Shipping</dt><dd class="text-gray-900">{{ \Minishop\Support\Money::format($order->shipping_amount) }}</dd></div>
            @if ($order->tax_amount)
                <div class="flex justify-between"><dt class="text-gray-500">Tax</dt><dd class="text-gray-900">{{ \Minishop\Support\Money::format($order->tax_amount) }}</dd></div>
            @endif
            <div class="flex justify-between border-t border-gray-200 pt-2 text-base font-semibold">
                <dt>Total</dt><dd>{{ \Minishop\Support\Money::format($order->total_amount) }}</dd>
            </div>
        </dl>
    </div>

    <div class="mt-8 text-center">
        <a href="{{ route('storefront.products.index') }}" class="font-medium text-brand-600 hover:underline">Continue shopping</a>
    </div>

    @include('minishop::analytics.purchase', ['order' => $order])
@endsection
