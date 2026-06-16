<?php

namespace Minishop\Http\Controllers\Storefront;

use Illuminate\Http\Request;
use Minishop\Actions\SearchProducts;
use Minishop\Http\Controllers\Controller;
use Minishop\Models\Category;
use Minishop\Models\Product;
use Minishop\Models\Review;
use Minishop\Models\StoreSettings;
use Minishop\Models\Tag;
use Minishop\Rendering\StorefrontRendererContract;

class ProductController extends Controller
{
    public function __construct(
        private StorefrontRendererContract $renderer,
        private SearchProducts $search,
    ) {}

    public function index(Request $request): mixed
    {
        $filters = $request->only(['category', 'tag', 'search', 'price_min', 'price_max', 'stock']);

        $products = $this->search->paginate($filters, perPage: 24, tap: function ($query): void {
            $query->with(['categories', 'tags', 'images', 'options.values', 'variants.optionValues', 'variants.images']);
        });

        $categories = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $tags = Tag::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'color']);

        return $this->renderer->render('storefront/Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'tags' => $tags,
            'filters' => $filters,
            'sale_discount_percentage' => StoreSettings::current()->sale_discount_percentage ?? 0,
        ]);
    }

    public function show(Request $request, Product $product): mixed
    {
        abort_unless($product->is_active, 404);

        $product->load([
            'categories',
            'tags',
            'images',
            'options.values',
            'variants.optionValues',
            'variants.images',
            'relatedProducts' => function ($query): void {
                $query->where('is_active', true)->with('images')->limit(8);
            },
        ]);

        if ($product->isBundled()) {
            $product->load(['bundleItems.componentProduct.images', 'bundleItems.componentVariant.optionValues.option']);
        }

        $product->load('approvedReviews.user')
            ->loadCount('approvedReviews')
            ->loadAvg('approvedReviews', 'rating');

        $user = $request->user();
        $userReview = $user
            ? Review::query()->where('product_id', $product->id)->where('user_id', $user->id)->first()
            : null;

        return $this->renderer->render('storefront/Products/Show', [
            'product' => $product,
            'in_stock' => $product->getEffectiveStock() > 0,
            'sale_discount_percentage' => StoreSettings::current()->sale_discount_percentage ?? 0,
            'userReview' => $userReview,
            'canReview' => $user && Review::userMayReview($user, $product),
        ]);
    }
}
