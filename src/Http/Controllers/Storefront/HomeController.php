<?php

namespace Minishop\Http\Controllers\Storefront;

use Minishop\Http\Controllers\Controller;
use Minishop\Models\Category;
use Minishop\Models\Product;
use Minishop\Rendering\StorefrontRendererContract;

class HomeController extends Controller
{
    public function __construct(private StorefrontRendererContract $renderer) {}

    public function __invoke(): mixed
    {
        $featuredProducts = Product::query()
            ->where('is_active', true)
            ->with(['categories', 'images'])
            ->latest()
            ->limit(8)
            ->get();

        $categories = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $this->renderer->render('storefront/Home', [
            'featuredProducts' => $featuredProducts,
            'categories' => $categories,
        ]);
    }
}
