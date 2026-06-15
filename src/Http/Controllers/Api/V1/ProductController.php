<?php

namespace Minishop\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Minishop\Http\Controllers\Controller;
use Minishop\Http\Resources\ProductCollection;
use Minishop\Http\Resources\ProductResource;
use Minishop\Models\Product;

class ProductController extends Controller
{
    public function index(Request $request): ProductCollection
    {
        $products = Product::query()
            ->where('is_active', true)
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->with(['categories', 'images'])
            ->withCount('approvedReviews')
            ->withAvg('approvedReviews', 'rating')
            ->paginate(20);

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
