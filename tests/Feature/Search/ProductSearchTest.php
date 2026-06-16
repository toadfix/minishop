<?php

namespace Minishop\Tests\Feature\Search;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Minishop\Livewire\ProductList;
use Minishop\Models\Category;
use Minishop\Models\Product;
use Minishop\Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    private function ids(array $json): array
    {
        return collect($json['data'])->pluck('id')->sort()->values()->all();
    }

    public function test_search_matches_on_sku(): void
    {
        $match = Product::factory()->create(['name' => 'Wireless Mouse', 'sku' => 'WM-2024-XL']);
        Product::factory()->create(['name' => 'Keyboard', 'sku' => 'KB-001']);

        $json = $this->getJson('/api/v1/products?search=WM-2024-XL')->assertOk()->json();

        $this->assertSame([$match->id], $this->ids($json));
    }

    public function test_search_matches_on_name(): void
    {
        $match = Product::factory()->create(['name' => 'Aurora Desk Lamp']);
        Product::factory()->create(['name' => 'Ceramic Mug']);

        $json = $this->getJson('/api/v1/products?search=aurora')->assertOk()->json();

        $this->assertSame([$match->id], $this->ids($json));
    }

    public function test_search_combines_with_a_category_facet(): void
    {
        $lighting = Category::factory()->create(['slug' => 'lighting']);

        $inCategory = Product::factory()->create(['name' => 'Studio Lamp']);
        $inCategory->categories()->attach($lighting);

        $otherCategory = Product::factory()->create(['name' => 'Studio Lamp Deluxe']);

        $json = $this->getJson('/api/v1/products?search=studio&category=lighting')->assertOk()->json();

        $this->assertSame([$inCategory->id], $this->ids($json));
    }

    public function test_empty_search_returns_all_active_products(): void
    {
        Product::factory()->count(3)->create();
        Product::factory()->inactive()->create();

        $json = $this->getJson('/api/v1/products')->assertOk()->json();

        $this->assertCount(3, $json['data']);
    }

    public function test_livewire_product_list_filters_by_search(): void
    {
        Product::factory()->create(['name' => 'SEARCHABLE-WIDGET']);
        Product::factory()->create(['name' => 'OTHER-THING']);

        Livewire::test(ProductList::class)
            ->set('search', 'SEARCHABLE-WIDGET')
            ->assertSee('SEARCHABLE-WIDGET')
            ->assertDontSee('OTHER-THING');
    }
}
