<?php

namespace Minishop\Http\Controllers\Storefront;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Minishop\Enums\ReviewStatus;
use Minishop\Http\Controllers\Controller;
use Minishop\Models\Product;
use Minishop\Models\Review;

class ReviewController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $user = $request->user();

        // Verified-buyer gate: only customers who purchased the product (and
        // have not already reviewed it) may submit a review.
        abort_unless(Review::userMayReview($user, $product), 403);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $product->reviews()->create([
            'user_id' => $user->id,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'body' => $validated['body'],
            'status' => ReviewStatus::Pending,
        ]);

        return redirect()
            ->route('storefront.products.show', $product->slug)
            ->with('status', 'Thanks! Your review will appear once it has been approved.');
    }
}
