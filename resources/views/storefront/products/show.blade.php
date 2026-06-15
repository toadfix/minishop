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

    <section class="mt-16 border-t border-gray-200 pt-10">
        <div class="flex items-baseline justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Customer reviews</h2>
            @if ($product->approved_reviews_count > 0)
                <div class="text-sm text-gray-600">
                    @include('minishop::storefront.partials.stars', ['rating' => $product->approved_reviews_avg_rating])
                    <span class="ml-1">{{ number_format($product->approved_reviews_avg_rating, 1) }} out of 5 ({{ $product->approved_reviews_count }})</span>
                </div>
            @endif
        </div>

        @if (session('status'))
            <p class="mt-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</p>
        @endif

        @if ($canReview)
            <form method="POST" action="{{ route('storefront.products.reviews.store', $product->slug) }}" class="mt-6 space-y-4 rounded-lg border border-gray-200 bg-white p-6">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700">Rating</label>
                    <select name="rating" required class="mt-1 block w-32 rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                        @for ($i = 5; $i >= 1; $i--)<option value="{{ $i }}">{{ $i }} star{{ $i > 1 ? 's' : '' }}</option>@endfor
                    </select>
                    @error('rating') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Title <span class="text-gray-400">(optional)</span></label>
                    <input type="text" name="title" value="{{ old('title') }}" maxlength="255"
                           class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Your review</label>
                    <textarea name="body" rows="4" required maxlength="2000"
                              class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">{{ old('body') }}</textarea>
                    @error('body') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Submit review</button>
            </form>
        @elseif ($userReview)
            <p class="mt-4 text-sm text-gray-500">
                @if ($userReview->status === \Minishop\Enums\ReviewStatus::Approved)
                    You reviewed this product.
                @else
                    Your review is {{ $userReview->status->label() }}.
                @endif
            </p>
        @endif

        <div class="mt-8 space-y-6">
            @forelse ($product->approvedReviews as $review)
                <article class="border-t border-gray-100 pt-6 first:border-0 first:pt-0">
                    <div class="flex items-center gap-2">
                        @include('minishop::storefront.partials.stars', ['rating' => $review->rating])
                        @if ($review->title)<span class="text-sm font-semibold text-gray-900">{{ $review->title }}</span>@endif
                    </div>
                    <p class="mt-2 text-sm text-gray-700">{{ $review->body }}</p>
                    <p class="mt-2 text-xs text-gray-400">
                        {{ $review->user->name }} · <span class="text-green-600">Verified purchase</span> · {{ $review->created_at->format('M j, Y') }}
                    </p>
                </article>
            @empty
                <p class="text-sm text-gray-500">No reviews yet.</p>
            @endforelse
        </div>
    </section>

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
