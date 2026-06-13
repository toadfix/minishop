@extends('minishop::storefront.layout')

@section('title', 'Payment')

@section('content')
    <div class="mx-auto max-w-xl">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Complete your payment</h1>
        <p class="mt-2 text-gray-500">Order <span class="font-medium text-gray-900">{{ $order->order_number }}</span></p>

        <div class="mt-8 rounded-lg border border-gray-200 bg-white p-6">
            <ul class="divide-y divide-gray-100 text-sm">
                @foreach ($order->items as $item)
                    <li class="flex justify-between gap-2 py-3">
                        <span class="text-gray-700">{{ $item->quantity }} &times; {{ $item->product_name }}</span>
                        <span class="text-gray-900">{{ \Minishop\Support\Money::format($item->unit_price * $item->quantity) }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="mt-4 flex justify-between border-t border-gray-200 pt-4 text-base font-semibold">
                <span>Total due</span>
                <span>{{ \Minishop\Support\Money::format($order->total_amount) }}</span>
            </div>
        </div>

        {{--
            Payment element. The package exposes POST
            storefront.checkout.payment.stripe to create a PaymentIntent; wire
            your gateway's client SDK (e.g. Stripe Elements) here using your
            publishable key. Webhooks confirm the order once paid.
        --}}
        <div id="minishop-payment-element" class="mt-8" data-intent-url="{{ route('storefront.checkout.payment.stripe', $order->order_number) }}">
            <p class="rounded-md bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                Connect your payment provider's client SDK to this page to collect payment.
            </p>
        </div>
    </div>
@endsection
