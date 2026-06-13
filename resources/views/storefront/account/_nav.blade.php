@php($current = request()->route()?->getName())
<nav class="flex flex-wrap gap-1 rounded-lg border border-gray-200 bg-white p-2 text-sm font-medium">
    @foreach ([
        'account.dashboard' => 'Dashboard',
        'account.orders.index' => 'Orders',
        'account.address.edit' => 'Address',
        'account.payment.index' => 'Payment',
    ] as $route => $label)
        <a href="{{ route($route) }}"
           class="rounded-md px-3 py-2 {{ str_starts_with((string) $current, rtrim($route, '.index')) ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-50' }}">
            {{ $label }}
        </a>
    @endforeach
</nav>
