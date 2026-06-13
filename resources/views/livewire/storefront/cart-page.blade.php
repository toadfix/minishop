<div>
    @if ($cart->items->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-300 bg-white p-12 text-center">
            <p class="text-gray-500">Your cart is empty.</p>
            <a href="{{ route('storefront.products.index') }}" class="mt-4 inline-block font-medium text-brand-600 hover:underline">
                Browse products
            </a>
        </div>
    @else
        <div class="grid gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <ul role="list" class="divide-y divide-gray-200 rounded-lg border border-gray-200 bg-white">
                    @foreach ($cart->items as $item)
                        <li class="flex gap-4 p-4" wire:key="item-{{ $item->id }}">
                            @if ($item->product->images->isNotEmpty())
                                <img src="{{ $item->product->images->first()->url }}" alt="" class="h-20 w-20 flex-none rounded-md object-cover">
                            @else
                                <div class="h-20 w-20 flex-none rounded-md bg-gray-100"></div>
                            @endif

                            <div class="flex flex-1 flex-col">
                                <div class="flex justify-between gap-2">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $item->product->name }}</p>
                                        @if ($item->variant)
                                            <p class="text-sm text-gray-500">{{ $item->variant->optionValues->pluck('value')->join(' / ') }}</p>
                                        @endif
                                    </div>
                                    <p class="font-medium text-gray-900">{{ \Minishop\Support\Money::format($item->unit_price * $item->quantity) }}</p>
                                </div>

                                <div class="mt-auto flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <button type="button" class="h-7 w-7 rounded border border-gray-300 text-gray-600 hover:bg-gray-50"
                                                wire:click="updateQuantity({{ $item->id }}, {{ $item->quantity - 1 }})">&minus;</button>
                                        <span class="w-8 text-center text-sm">{{ $item->quantity }}</span>
                                        <button type="button" class="h-7 w-7 rounded border border-gray-300 text-gray-600 hover:bg-gray-50"
                                                wire:click="updateQuantity({{ $item->id }}, {{ $item->quantity + 1 }})">+</button>
                                    </div>
                                    <button type="button" class="text-sm text-red-600 hover:underline" wire:click="remove({{ $item->id }})">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <button type="button" wire:click="clear" class="mt-4 text-sm text-gray-500 hover:text-red-600">Clear cart</button>
            </div>

            <aside class="h-fit rounded-lg border border-gray-200 bg-white p-6">
                <h2 class="text-lg font-semibold text-gray-900">Order summary</h2>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Subtotal</dt>
                        <dd class="font-medium text-gray-900">{{ \Minishop\Support\Money::format($subtotal) }}</dd>
                    </div>
                    <p class="text-xs text-gray-400">Shipping &amp; taxes calculated at checkout.</p>
                </dl>
                <a href="{{ route('storefront.checkout.create') }}"
                   class="mt-6 block rounded-md bg-brand-600 px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-brand-700">
                    Checkout
                </a>
            </aside>
        </div>
    @endif
</div>
