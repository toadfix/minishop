<?php

namespace Minishop\Tests\Feature\Storefront;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Models\Category;
use Minishop\Models\Product;
use Minishop\Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_successfully(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertViewIs('minishop::storefront.home');
    }

    public function test_home_page_passes_featured_products(): void
    {
        Product::factory(3)->create();

        $this->get(route('home'))
            ->assertViewHas('featuredProducts', fn ($products) => $products->count() === 3);
    }

    public function test_home_page_excludes_inactive_products(): void
    {
        Product::factory(2)->create();
        Product::factory(2)->inactive()->create();

        $this->get(route('home'))
            ->assertViewHas('featuredProducts', fn ($products) => $products->count() === 2);
    }

    public function test_home_page_limits_featured_products_to_eight(): void
    {
        Product::factory(12)->create();

        $this->get(route('home'))
            ->assertViewHas('featuredProducts', fn ($products) => $products->count() === 8);
    }

    public function test_home_page_passes_active_categories(): void
    {
        Category::factory()->create(['is_active' => true, 'parent_id' => null]);
        Category::factory()->create(['is_active' => false, 'parent_id' => null]);

        $this->get(route('home'))
            ->assertViewHas('categories', fn ($categories) => $categories->count() === 1);
    }

    public function test_home_page_excludes_child_categories_from_top_level(): void
    {
        $parent = Category::factory()->create(['is_active' => true, 'parent_id' => null]);
        Category::factory()->create(['is_active' => true, 'parent_id' => $parent->id]);

        $this->get(route('home'))
            ->assertViewHas('categories', fn ($categories) => $categories->count() === 1);
    }
}
