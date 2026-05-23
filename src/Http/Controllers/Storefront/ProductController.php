<?php

namespace Minishop\Http\Controllers\Storefront;

use Illuminate\Http\Request;
use Minishop\Http\Controllers\Controller;
use Minishop\Models\Category;
use Minishop\Models\Product;
use Minishop\Models\StoreSettings;
use Minishop\Models\Tag;
use Minishop\Rendering\StorefrontRendererContract;

class ProductController extends Controller
{
    public function __construct(private StorefrontRendererContract $renderer) {}

    public function index(Request $request): mixed
    {
        $products = Product::query()
            ->where('is_active', true)
            ->with(['categories', 'tags', 'images', 'options.values', 'variants.optionValues', 'variants.images'])
            ->when($request->filled('category'), function ($query) use ($request): void {
                $query->whereHas('categories', fn ($q) => $q->where('slug', $request->string('category')));
            })
            ->when($request->filled('tag'), function ($query) use ($request): void {
                $query->whereHas('tags', fn ($q) => $q->where('slug', $request->string('tag')));
            })
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search');
                $query->where(function ($q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('price_min'), function ($query) use ($request): void {
                $query->where('price', '>=', (int) round((float) $request->input('price_min') * 100));
            })
            ->when($request->filled('price_max'), function ($query) use ($request): void {
                $query->where('price', '<=', (int) round((float) $request->input('price_max') * 100));
            })
            ->when($request->filled('stock'), function ($query) use ($request): void {
                // Exclude bundled products — their stock is computed, not stored
                $query->where('type', '!=', 'bundled');

                if ($request->input('stock') === 'in_stock') {
                    $query->where('stock_quantity', '>', 0);
                } elseif ($request->input('stock') === 'out_of_stock') {
                    $query->where('stock_quantity', 0);
                }
            })
            ->paginate(24)
            ->withQueryString();

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
            'filters' => $request->only(['category', 'tag', 'search', 'price_min', 'price_max', 'stock']),
            'sale_discount_percentage' => StoreSettings::current()->sale_discount_percentage ?? 0,
        ]);
    }

    public function show(Product $product): mixed
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

        return $this->renderer->render('storefront/Products/Show', [
            'product' => $product,
            'in_stock' => $product->getEffectiveStock() > 0,
            'sale_discount_percentage' => StoreSettings::current()->sale_discount_percentage ?? 0,
        ]);
    }
}
