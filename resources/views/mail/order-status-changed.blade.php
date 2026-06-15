@component('mail::message')
# Your Order Has Been {{ $order->status->label() }}

Hi {{ $order->customer->user->name }},

@switch($order->status->value)
@case('shipped')
Great news! Your order **{{ $order->order_number }}** has been shipped and is on its way to you.
@if ($order->tracking_number)

@if ($order->carrier)**Carrier:** {{ $order->carrier }}
@endif
**Tracking number:** {{ $order->tracking_number }}
@endif
@break
@case('delivered')
Your order **{{ $order->order_number }}** has been delivered. We hope you enjoy your purchase!
@break
@case('cancelled')
Your order **{{ $order->order_number }}** has been cancelled. If you have any questions, please contact us.
@break
@default
Your order **{{ $order->order_number }}** status has been updated to **{{ $order->status->label() }}**.
@endswitch

---

## Order Summary

**Order Number:** {{ $order->order_number }}
**Status:** {{ $order->status->label() }}

@component('mail::table')
| Product | Qty | Total |
|:--------|----:|------:|
@foreach ($order->items as $item)
| {{ $item->product_name }} | {{ $item->quantity }} | {{ $currency }} {{ number_format($item->subtotal / 100, 2) }} |
@endforeach
@endcomponent

| | |
|:--|--:|
| Subtotal | {{ $currency }} {{ number_format($order->subtotal / 100, 2) }} |
@if ($order->discount_amount > 0)
| Discount | -{{ $currency }} {{ number_format($order->discount_amount / 100, 2) }} |
@endif
| Shipping | @if($order->shipping_amount === 0) Free @else {{ $currency }} {{ number_format($order->shipping_amount / 100, 2) }} @endif |
| Tax | {{ $currency }} {{ number_format($order->tax_amount / 100, 2) }} |
| **Total** | **{{ $currency }} {{ number_format($order->total_amount / 100, 2) }}** |

@component('mail::button', ['url' => route('storefront.order.confirmation', $order->order_number)])
View Order
@endcomponent

Thanks,
{{ config('app.name') }}
@endcomponent
