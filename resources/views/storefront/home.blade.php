@extends('minishop::storefront.layout')

@section('title', config('app.name', 'Shop'))

@section('content')
    <section class="mb-12 rounded-2xl bg-brand-600 px-8 py-16 text-center text-white">
        <h1 class="text-4xl font-bold tracking-tight">Welcome to {{ config('app.name', 'our shop') }}</h1>
        <p class="mx-auto mt-3 max-w-xl text-brand-50">Discover our latest products, handpicked for you.</p>
        <a href="{{ route('storefront.products.index') }}"
           class="mt-6 inline-block rounded-md bg-white px-6 py-3 text-sm font-semibold text-brand-700 hover:bg-brand-50">
            Shop all products
        </a>
    </section>

    @if ($categories->isNotEmpty())
        <section class="mb-12">
            <h2 class="text-lg font-semibold text-gray-900">Shop by category</h2>
            <div class="mt-4 flex flex-wrap gap-3">
                @foreach ($categories as $category)
                    <a href="{{ route('storefront.products.index', ['category' => $category->slug]) }}"
                       class="rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:border-brand-500 hover:text-brand-600">
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section>
        <h2 class="text-lg font-semibold text-gray-900">Featured products</h2>
        @if ($featuredProducts->isEmpty())
            <p class="mt-4 text-gray-500">No products yet. Check back soon.</p>
        @else
            <div class="mt-4 grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($featuredProducts as $product)
                    <a href="{{ route('storefront.products.show', $product) }}" class="group">
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
    </section>
@endsection
