<?php

namespace Minishop\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Models\Product;
use Minishop\Models\ProductVariant;
use Minishop\Tests\TestCase;

class BundleItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_bundle_item_belongs_to_bundle_product(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create();
        $component = Product::factory()->create();

        $item = $bundle->bundleItems()->create([
            'component_product_id' => $component->id,
            'quantity' => 1,
        ]);

        $this->assertTrue($item->bundleProduct->is($bundle));
    }

    public function test_bundle_item_belongs_to_component_product(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create();
        $component = Product::factory()->create();

        $item = $bundle->bundleItems()->create([
            'component_product_id' => $component->id,
            'quantity' => 1,
        ]);

        $this->assertTrue($item->componentProduct->is($component));
    }

    public function test_bundle_item_belongs_to_variant(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create();
        $component = Product::factory()->variable()->create();
        $variant = ProductVariant::factory()->for($component)->create();

        $item = $bundle->bundleItems()->create([
            'component_product_id' => $component->id,
            'component_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->assertTrue($item->componentVariant->is($variant));
    }

    public function test_bundle_item_variant_is_nullable(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create();
        $component = Product::factory()->create();

        $item = $bundle->bundleItems()->create([
            'component_product_id' => $component->id,
            'quantity' => 1,
        ]);

        $this->assertNull($item->componentVariant);
    }
}
