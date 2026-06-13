<div class="grid gap-8 lg:grid-cols-4">
    <aside class="space-y-6 lg:col-span-1">
        <div>
            <label class="block text-sm font-medium text-gray-700">Search</label>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search products…"
                   class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
        </div>

        <div>
            <p class="text-sm font-medium text-gray-700">Categories</p>
            <ul class="mt-2 space-y-1 text-sm">
                <li>
                    <button type="button" wire:click="$set('category', '')"
                            class="{{ $category === '' ? 'font-semibold text-brand-600' : 'text-gray-600 hover:text-brand-600' }}">
                        All products
                    </button>
                </li>
                @foreach ($categories as $cat)
                    <li>
                        <button type="button" wire:click="$set('category', '{{ $cat->slug }}')"
                                class="{{ $category === $cat->slug ? 'font-semibold text-brand-600' : 'text-gray-600 hover:text-brand-600' }}">
                            {{ $cat->name }}
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>

        @if ($search !== '' || $category !== '')
            <button type="button" wire:click="clearFilters" class="text-sm text-gray-500 hover:text-red-600">Clear filters</button>
        @endif
    </aside>

    <div class="lg:col-span-3">
        <div wire:loading.class="opacity-50">
            @if ($products->isEmpty())
                <div class="rounded-lg border border-dashed border-gray-300 bg-white p-12 text-center text-gray-500">
                    No products match your search.
                </div>
            @else
                <div class="grid grid-cols-2 gap-6 sm:grid-cols-3">
                    @foreach ($products as $product)
                        <a href="{{ route('storefront.products.show', $product) }}" class="group" wire:key="product-{{ $product->id }}">
                            <div class="aspect-square overflow-hidden rounded-lg bg-gray-100">
                                @if ($product->images->isNotEmpty())
                                    <img src="{{ $product->images->first()->url }}" alt="{{ $product->name }}"
                                         class="h-full w-full object-cover transition group-hover:scale-105">
                                @endif
                            </div>
                            <p class="mt-2 text-sm font-medium text-gray-900">{{ $product->name }}</p>
                            <p class="text-sm text-gray-500">{{ \Minishop\Support\Money::format($product->price) }}</p>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="mt-8">
            {{ $products->links() }}
        </div>
    </div>
</div>
