@extends('minishop::storefront.layout')

@section('title', 'My account')

@section('content')
    <h1 class="mb-6 text-2xl font-bold tracking-tight text-gray-900">My account</h1>
    @include('minishop::storefront.account._nav')

    <div class="mt-6 grid gap-6 sm:grid-cols-2">
        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <p class="text-sm text-gray-500">Total orders</p>
            <p class="mt-1 text-3xl font-bold text-gray-900">{{ $totalOrders }}</p>
        </div>
    </div>

    <h2 class="mt-10 text-lg font-semibold text-gray-900">Recent orders</h2>
    @if ($recentOrders->isEmpty())
        <p class="mt-2 text-gray-500">You have not placed any orders yet.</p>
    @else
        <ul class="mt-4 divide-y divide-gray-100 rounded-lg border border-gray-200 bg-white">
            @foreach ($recentOrders as $order)
                <li class="flex items-center justify-between gap-4 p-4">
                    <div>
                        <a href="{{ route('account.orders.show', $order) }}" class="font-medium text-brand-600 hover:underline">{{ $order->order_number }}</a>
                        <p class="text-sm text-gray-500">{{ $order->created_at->format('M j, Y') }} &middot; {{ ucfirst($order->status->value ?? $order->status) }}</p>
                    </div>
                    <span class="font-medium text-gray-900">{{ \Minishop\Support\Money::format($order->total_amount) }}</span>
                </li>
            @endforeach
        </ul>
    @endif
@endsection
