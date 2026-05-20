<?php

namespace Minishop\Tests\Feature\Storefront;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Models\Category;
use Minishop\Models\Product;
use Minishop\Models\ProductImage;
use Minishop\Models\ProductVariant;
use Minishop\Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_index_renders_successfully(): void
    {
        $this->get(route('storefront.products.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('storefront/Products/Index'));
    }

    public function test_products_index_only_shows_active_products(): void
    {
        Product::factory(3)->create();
        Product::factory(2)->inactive()->create();

        $this->get(route('storefront.products.index'))
            ->assertInertia(fn ($page) => $page->has('products.data', 3));
    }

    public function test_products_index_can_filter_by_category_slug(): void
    {
        $category = Category::factory()->create(['slug' => 'apparel', 'is_active' => true]);
        $product = Product::factory()->create();
        $product->categories()->attach($category);
        Product::factory()->create();

        $this->get(route('storefront.products.index', ['category' => 'apparel']))
            ->assertInertia(fn ($page) => $page->has('products.data', 1));
    }

    public function test_products_index_can_search_by_name(): void
    {
        Product::factory()->create(['name' => 'Red Ceramic Mug']);
        Product::factory()->create(['name' => 'Blue T-Shirt']);

        $this->get(route('storefront.products.index', ['search' => 'Ceramic']))
            ->assertInertia(fn ($page) => $page->has('products.data', 1));
    }

    public function test_products_index_passes_categories_and_filters(): void
    {
        $this->get(route('storefront.products.index', ['category' => 'home', 'search' => 'mug']))
            ->assertOk()
            ->assertInertia(function ($page) {
                $page->has('categories')
                    ->where('filters.category', 'home')
                    ->where('filters.search', 'mug');
            });
    }

    public function test_products_index_can_be_filtered_by_price_min(): void
    {
        Product::factory()->create(['name' => 'Cheap', 'price' => 500]);
        Product::factory()->create(['name' => 'Mid', 'price' => 2000]);
        Product::factory()->create(['name' => 'Expensive', 'price' => 5000]);

        $this->get(route('storefront.products.index', ['price_min' => '15']))
            ->assertInertia(fn ($page) => $page->has('products.data', 2));
    }

    public function test_products_index_can_be_filtered_by_price_max(): void
    {
        Product::factory()->create(['name' => 'Cheap', 'price' => 500]);
        Product::factory()->create(['name' => 'Mid', 'price' => 2000]);
        Product::factory()->create(['name' => 'Expensive', 'price' => 5000]);

        $this->get(route('storefront.products.index', ['price_max' => '20']))
            ->assertInertia(fn ($page) => $page->has('products.data', 2));
    }

    public function test_products_index_can_be_filtered_by_price_range(): void
    {
        Product::factory()->create(['name' => 'Cheap', 'price' => 500]);
        Product::factory()->create(['name' => 'Mid', 'price' => 2000]);
        Product::factory()->create(['name' => 'Expensive', 'price' => 5000]);

        $this->get(route('storefront.products.index', ['price_min' => '15', 'price_max' => '25']))
            ->assertInertia(fn ($page) => $page->has('products.data', 1));
    }

    public function test_products_index_can_be_filtered_by_stock_in_stock(): void
    {
        Product::factory()->simple()->create(['name' => 'Available', 'stock_quantity' => 5]);
        Product::factory()->simple()->create(['name' => 'Sold Out', 'stock_quantity' => 0]);

        $this->get(route('storefront.products.index', ['stock' => 'in_stock']))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Available')
            );
    }

    public function test_products_index_can_be_filtered_by_stock_out_of_stock(): void
    {
        Product::factory()->simple()->create(['name' => 'Available', 'stock_quantity' => 5]);
        Product::factory()->simple()->create(['name' => 'Sold Out', 'stock_quantity' => 0]);

        $this->get(route('storefront.products.index', ['stock' => 'out_of_stock']))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Sold Out')
            );
    }

    public function test_products_index_stock_filter_excludes_bundled_products(): void
    {
        Product::factory()->simple()->create(['name' => 'Simple Out', 'stock_quantity' => 0]);
        Product::factory()->bundledEmpty()->create(['name' => 'Bundle', 'stock_quantity' => 0]);

        $this->get(route('storefront.products.index', ['stock' => 'out_of_stock']))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Simple Out')
            );
    }

    public function test_products_index_search_includes_description(): void
    {
        Product::factory()->create(['name' => 'Widget', 'description' => 'A handcrafted ceramic bowl']);
        Product::factory()->create(['name' => 'Gadget', 'description' => 'A leather wallet']);

        $this->get(route('storefront.products.index', ['search' => 'ceramic bowl']))
            ->assertInertia(fn ($page) => $page->has('products.data', 1));
    }

    public function test_products_index_passes_new_filter_params_to_view(): void
    {
        $this->get(route('storefront.products.index', ['price_min' => '10', 'price_max' => '50', 'stock' => 'in_stock']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.price_min', '10')
                ->where('filters.price_max', '50')
                ->where('filters.stock', 'in_stock')
            );
    }

    public function test_product_show_renders_for_active_product(): void
    {
        $product = Product::factory()->create();

        $this->get(route('storefront.products.show', $product))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('storefront/Products/Show')
                ->where('product.slug', $product->slug)
            );
    }

    public function test_inactive_product_returns_404(): void
    {
        $product = Product::factory()->inactive()->create();

        $this->get(route('storefront.products.show', $product))
            ->assertNotFound();
    }

    public function test_nonexistent_product_returns_404(): void
    {
        $this->get(route('storefront.products.show', 'non-existent-slug'))
            ->assertNotFound();
    }

    public function test_product_show_includes_variant_images(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        ProductImage::factory()->create([
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'sort_order' => 0,
        ]);

        $this->get(route('storefront.products.show', $product))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('product.variants.0.images', 1)
            );
    }

    public function test_product_show_includes_seo_meta_fields(): void
    {
        $product = Product::factory()->create([
            'meta_title' => 'Custom SEO Title',
            'meta_description' => 'A custom meta description for search engines.',
        ]);

        $this->get(route('storefront.products.show', $product))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('product.meta_title', 'Custom SEO Title')
                ->where('product.meta_description', 'A custom meta description for search engines.')
            );
    }

    public function test_product_show_related_products_appear_with_correct_slugs(): void
    {
        $product = Product::factory()->create();
        $related1 = Product::factory()->create();
        $related2 = Product::factory()->create();
        $product->relatedProducts()->attach([$related1->id, $related2->id]);

        $this->get(route('storefront.products.show', $product))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('product.related_products', 2)
                ->where('product.related_products.0.slug', $related1->slug)
                ->where('product.related_products.1.slug', $related2->slug)
            );
    }

    public function test_product_show_excludes_inactive_related_products(): void
    {
        $product = Product::factory()->create();
        $activeRelated = Product::factory()->create();
        $inactiveRelated = Product::factory()->inactive()->create();
        $product->relatedProducts()->attach([$activeRelated->id, $inactiveRelated->id]);

        $this->get(route('storefront.products.show', $product))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('product.related_products', 1)
                ->where('product.related_products.0.id', $activeRelated->id)
            );
    }

    public function test_product_show_limits_related_products_to_eight(): void
    {
        $product = Product::factory()->create();
        $relatedProducts = Product::factory(10)->create();
        $product->relatedProducts()->attach($relatedProducts->pluck('id')->toArray());

        $this->get(route('storefront.products.show', $product))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('product.related_products', 8)
            );
    }

    public function test_product_show_has_no_related_products_when_none_added(): void
    {
        $product = Product::factory()->create();

        $this->get(route('storefront.products.show', $product))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('product.related_products', 0)
            );
    }

    public function test_product_show_images_only_contains_product_level_images(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        ProductImage::factory()->create(['product_id' => $product->id, 'variant_id' => null]);
        ProductImage::factory()->create(['product_id' => $product->id, 'variant_id' => $variant->id]);

        $this->get(route('storefront.products.show', $product))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('product.images', 1)
            );
    }
}
