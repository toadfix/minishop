{{--
    Renders a product's primary image, or a centered placeholder icon when the
    product has none. Designed to fill an existing aspect-ratio container.

    @include('minishop::storefront.partials.product-image', [
        'product' => $product,
        'class'   => 'h-full w-full object-cover',  // optional
        'alt'     => $product->name,                // optional
    ])
--}}
@if ($product->images->isNotEmpty())
    <img src="{{ $product->images->first()->url }}"
         alt="{{ $alt ?? $product->name }}"
         class="{{ $class ?? 'h-full w-full object-cover' }}">
@else
    <div class="flex h-full w-full items-center justify-center bg-gray-100 text-gray-300">
        <svg class="h-1/3 w-1/3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
        </svg>
    </div>
@endif
