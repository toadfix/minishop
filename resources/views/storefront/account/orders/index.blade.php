@extends('minishop::storefront.layout')

@section('title', 'My orders')

@section('content')
    <h1 class="mb-6 text-2xl font-bold tracking-tight text-gray-900">My account</h1>
    @include('minishop::storefront.account._nav')

    <h2 class="mt-6 text-lg font-semibold text-gray-900">Orders</h2>
    @if ($orders->isEmpty())
        <p class="mt-2 text-gray-500">You have not placed any orders yet.</p>
    @else
        <ul class="mt-4 divide-y divide-gray-100 rounded-lg border border-gray-200 bg-white">
            @foreach ($orders as $order)
                <li class="flex items-center justify-between gap-4 p-4">
                    <div>
                        <a href="{{ route('account.orders.show', $order) }}" class="font-medium text-brand-600 hover:underline">{{ $order->order_number }}</a>
                        <p class="text-sm text-gray-500">
                            {{ $order->created_at->format('M j, Y') }} &middot;
                            {{ $order->items->sum('quantity') }} item(s) &middot;
                            {{ ucfirst($order->status->value ?? $order->status) }}
                        </p>
                    </div>
                    <span class="font-medium text-gray-900">{{ \Minishop\Support\Money::format($order->total_amount) }}</span>
                </li>
            @endforeach
        </ul>

        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
@endsection
