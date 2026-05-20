<?php

namespace Minishop\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Enums\ProductType;
use Minishop\Models\Product;
use Minishop\Models\ProductVariant;
use Minishop\Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_slug_is_auto_generated_from_name_on_create(): void
    {
        $product = Product::factory()->create(['name' => 'My Awesome Product', 'slug' => '']);

        $this->assertSame('my-awesome-product', $product->slug);
    }

    public function test_slug_is_not_overwritten_on_update(): void
    {
        $product = Product::factory()->create(['name' => 'Original Name']);
        $originalSlug = $product->slug;

        $product->update(['name' => 'Updated Name']);

        $this->assertSame($originalSlug, $product->fresh()->slug);
    }

    public function test_out_of_stock_state_sets_stock_quantity_to_zero(): void
    {
        $product = Product::factory()->outOfStock()->create();

        $this->assertSame(0, $product->stock_quantity);
    }

    public function test_inactive_state_sets_is_active_to_false(): void
    {
        $product = Product::factory()->inactive()->create();

        $this->assertFalse($product->is_active);
    }

    public function test_route_key_name_is_slug(): void
    {
        $product = new Product;

        $this->assertSame('slug', $product->getRouteKeyName());
    }

    public function test_is_simple_is_variable_is_bundled_helpers(): void
    {
        $simple = Product::factory()->simple()->create();
        $variable = Product::factory()->variable()->create();
        $bundled = Product::factory()->bundledEmpty()->create();

        $this->assertTrue($simple->isSimple());
        $this->assertFalse($simple->isVariable());
        $this->assertFalse($simple->isBundled());

        $this->assertFalse($variable->isSimple());
        $this->assertTrue($variable->isVariable());
        $this->assertFalse($variable->isBundled());

        $this->assertFalse($bundled->isSimple());
        $this->assertFalse($bundled->isVariable());
        $this->assertTrue($bundled->isBundled());
    }

    public function test_get_effective_stock_returns_stock_quantity_for_simple(): void
    {
        $product = Product::factory()->simple()->create(['stock_quantity' => 42]);

        $this->assertSame(42, $product->getEffectiveStock());
    }

    public function test_get_effective_stock_returns_zero_when_any_component_is_exhausted(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create();
        $componentA = Product::factory()->create(['stock_quantity' => 10]);
        $componentB = Product::factory()->create(['stock_quantity' => 0]);

        $bundle->bundleItems()->create(['component_product_id' => $componentA->id, 'quantity' => 1]);
        $bundle->bundleItems()->create(['component_product_id' => $componentB->id, 'quantity' => 1]);

        $this->assertSame(0, $bundle->getEffectiveStock());
    }

    public function test_get_effective_stock_computes_min_across_components(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create();
        $componentA = Product::factory()->create(['stock_quantity' => 20]);
        $componentB = Product::factory()->create(['stock_quantity' => 9]);

        $bundle->bundleItems()->create(['component_product_id' => $componentA->id, 'quantity' => 2]);
        $bundle->bundleItems()->create(['component_product_id' => $componentB->id, 'quantity' => 3]);

        // componentA: floor(20/2) = 10, componentB: floor(9/3) = 3 → min = 3
        $this->assertSame(3, $bundle->getEffectiveStock());
    }

    public function test_get_effective_stock_uses_variant_stock_when_variant_specified(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create();
        $component = Product::factory()->variable()->create(['stock_quantity' => 100]);
        $variant = ProductVariant::factory()->for($component)->create(['stock_quantity' => 5]);

        $bundle->bundleItems()->create([
            'component_product_id' => $component->id,
            'component_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->assertSame(5, $bundle->getEffectiveStock());
    }

    public function test_bundle_with_zero_items_reports_zero_effective_stock(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create();

        $this->assertSame(0, $bundle->getEffectiveStock());
    }

    public function test_get_effective_weight_sums_component_weights(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create();
        $componentA = Product::factory()->create(['weight_grams' => 500]);
        $componentB = Product::factory()->create(['weight_grams' => 200]);

        $bundle->bundleItems()->create(['component_product_id' => $componentA->id, 'quantity' => 2]);
        $bundle->bundleItems()->create(['component_product_id' => $componentB->id, 'quantity' => 3]);

        // (500 * 2) + (200 * 3) = 1600
        $this->assertSame(1600, $bundle->getEffectiveWeight());
    }

    public function test_get_effective_weight_returns_weight_grams_for_simple(): void
    {
        $product = Product::factory()->simple()->create(['weight_grams' => 350]);

        $this->assertSame(350, $product->getEffectiveWeight());
    }

    public function test_type_change_silently_reverts(): void
    {
        $product = Product::factory()->simple()->create();

        $product->type = ProductType::Bundled;
        $product->save();

        $this->assertSame(ProductType::Simple, $product->fresh()->type);
    }

    public function test_deleting_product_blocked_when_component_of_bundle(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create();
        $component = Product::factory()->create();
        $bundle->bundleItems()->create(['component_product_id' => $component->id, 'quantity' => 1]);

        $this->expectException(HttpException::class);
        $component->delete();
    }
}
