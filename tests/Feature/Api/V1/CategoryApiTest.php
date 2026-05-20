<?php

namespace Minishop\Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Models\Category;
use Minishop\Models\Product;
use Minishop\Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_active_root_categories(): void
    {
        Category::factory(3)->create();
        Category::factory()->inactive()->create();

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_inactive_categories_are_excluded(): void
    {
        Category::factory(2)->create();
        Category::factory(3)->inactive()->create();

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_show_category_with_active_products(): void
    {
        $category = Category::factory()->create();
        $activeProducts = Product::factory(2)->create();
        $inactiveProduct = Product::factory()->inactive()->create();

        $category->products()->attach($activeProducts->pluck('id')->toArray());
        $category->products()->attach($inactiveProduct->id);

        $response = $this->getJson("/api/v1/categories/{$category->slug}");

        $response->assertOk()
            ->assertJsonPath('data.slug', $category->slug)
            ->assertJsonCount(2, 'data.products');
    }

    public function test_inactive_category_returns_404(): void
    {
        $category = Category::factory()->inactive()->create();

        $this->getJson("/api/v1/categories/{$category->slug}")
            ->assertNotFound();
    }
}
