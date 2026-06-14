@extends('minishop::storefront.layout')

@section('title', $product->name)

@section('content')
    <nav class="mb-6 text-sm text-gray-500">
        <a href="{{ route('storefront.products.index') }}" class="hover:text-brand-600">Products</a>
        <span class="px-2">/</span>
        <span class="text-gray-700">{{ $product->name }}</span>
    </nav>

    <div class="grid gap-10 lg:grid-cols-2">
        <div class="aspect-square overflow-hidden rounded-2xl bg-gray-100">
            @include('minishop::storefront.partials.product-image', ['product' => $product, 'class' => 'h-full w-full object-cover'])
        </div>

        <div>
            <h1 class="text-3xl font-bold tracking-tight text-gray-900">{{ $product->name }}</h1>
            <p class="mt-3 text-2xl font-semibold text-gray-900">{{ \Minishop\Support\Money::format($product->price) }}</p>

            <div class="mt-2">
                @if ($in_stock)
                    <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700">In stock</span>
                @else
                    <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700">Out of stock</span>
                @endif
            </div>

            @if ($product->description)
                <div class="prose prose-sm mt-6 max-w-none text-gray-600">{!! nl2br(e($product->description)) !!}</div>
            @endif

            @if ($in_stock)
                <div class="mt-8">
                    <livewire:minishop.add-to-cart :product="$product" />
                </div>
            @endif
        </div>
    </div>

    @if ($product->relatedProducts->isNotEmpty())
        <section class="mt-16">
            <h2 class="text-lg font-semibold text-gray-900">You may also like</h2>
            <div class="mt-4 grid grid-cols-2 gap-6 sm:grid-cols-4">
                @foreach ($product->relatedProducts as $related)
                    <a href="{{ route('storefront.products.show', $related) }}" class="group">
                        <div class="aspect-square overflow-hidden rounded-lg bg-gray-100">
                            @include('minishop::storefront.partials.product-image', ['product' => $related, 'class' => 'h-full w-full object-cover transition group-hover:scale-105'])
                        </div>
                        <p class="mt-2 text-sm font-medium text-gray-900">{{ $related->name }}</p>
                        <p class="text-sm text-gray-500">{{ \Minishop\Support\Money::format($related->price) }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
@endsection
