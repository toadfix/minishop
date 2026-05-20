@component('mail::message')
# Order Confirmed

Hi {{ $order->customer->user->name }},

Thank you for your order! We've received it and will get it ready for you shortly.

**Order Number:** {{ $order->order_number }}
**Date:** {{ $order->created_at->format('F j, Y') }}
**Payment Method:** {{ ucwords(str_replace('_', ' ', $order->payment_gateway)) }}

---

## Order Items

@component('mail::table')
| Product | Qty | Price | Total |
|:--------|----:|------:|------:|
@foreach ($order->items as $item)
| {{ $item->product_name }}@if($item->product_sku) *({{ $item->product_sku }})* @endif | {{ $item->quantity }} | {{ $currency }} {{ number_format($item->unit_price / 100, 2) }} | {{ $currency }} {{ number_format($item->subtotal / 100, 2) }} |
@endforeach
@endcomponent

---

## Order Summary

| | |
|:--|--:|
| Subtotal | {{ $currency }} {{ number_format($order->subtotal / 100, 2) }} |
@if ($order->discount_amount > 0)
| Discount | -{{ $currency }} {{ number_format($order->discount_amount / 100, 2) }} |
@endif
| Shipping | @if($order->shipping_amount === 0) Free @else {{ $currency }} {{ number_format($order->shipping_amount / 100, 2) }} @endif |
| Tax | {{ $currency }} {{ number_format($order->tax_amount / 100, 2) }} |
| **Total** | **{{ $currency }} {{ number_format($order->total_amount / 100, 2) }}** |

---

## Shipping Address

{{ $order->shipping_name }}
{{ $order->shipping_address_line1 }}
@if ($order->shipping_address_line2)
{{ $order->shipping_address_line2 }}
@endif
{{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_postcode }}
{{ $order->shipping_country }}

@component('mail::button', ['url' => route('storefront.order.confirmation', $order->order_number)])
View Order
@endcomponent

Thanks,
{{ config('app.name') }}
@endcomponent
