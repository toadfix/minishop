<?php

namespace Minishop\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Minishop\Actions\SearchProducts;
use Minishop\Http\Controllers\Controller;
use Minishop\Http\Resources\ProductCollection;
use Minishop\Http\Resources\ProductResource;
use Minishop\Models\Product;

class ProductController extends Controller
{
    public function __construct(private SearchProducts $search) {}

    public function index(Request $request): ProductCollection
    {
        $filters = $request->only(['search', 'category', 'tag', 'price_min', 'price_max', 'stock']);

        $products = $this->search->paginate($filters, perPage: 20, tap: function ($query): void {
            $query->with(['categories', 'images'])
                ->withCount('approvedReviews')
                ->withAvg('approvedReviews', 'rating');
        });

        return new ProductCollection($products);
    }

    public function show(Product $product): ProductResource
    {
        abort_unless($product->is_active, 404);

        $product->load(['categories', 'images'])
            ->loadCount('approvedReviews')
            ->loadAvg('approvedReviews', 'rating');

        return new ProductResource($product);
    }
}
