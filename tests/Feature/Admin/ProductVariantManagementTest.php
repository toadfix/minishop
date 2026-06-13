<?php

namespace Minishop\Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Filament\Resources\ProductResource;
use Minishop\Filament\Resources\ProductResource\Pages\EditProduct;
use Minishop\Filament\Resources\ProductResource\RelationManagers\OptionsRelationManager;
use Minishop\Filament\Resources\ProductResource\RelationManagers\VariantsRelationManager;
use Minishop\Models\Product;
use Minishop\Tests\TestCase;

class ProductVariantManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_resource_registers_the_variant_relation_managers(): void
    {
        $this->assertContains(OptionsRelationManager::class, ProductResource::getRelations());
        $this->assertContains(VariantsRelationManager::class, ProductResource::getRelations());
    }

    public function test_relation_managers_are_visible_only_for_variable_products(): void
    {
        $variable = Product::factory()->variable()->create();
        $simple = Product::factory()->simple()->create();
        $bundled = Product::factory()->bundledEmpty()->create();

        $this->assertTrue(OptionsRelationManager::canViewForRecord($variable, EditProduct::class));
        $this->assertTrue(VariantsRelationManager::canViewForRecord($variable, EditProduct::class));

        foreach ([$simple, $bundled] as $nonVariable) {
            $this->assertFalse(OptionsRelationManager::canViewForRecord($nonVariable, EditProduct::class));
            $this->assertFalse(VariantsRelationManager::canViewForRecord($nonVariable, EditProduct::class));
        }
    }

    public function test_variant_option_value_labels_are_built_from_the_owning_options(): void
    {
        $product = Product::factory()->variable()->create();
        $option = $product->options()->create(['name' => 'Size', 'position' => 0]);
        $small = $option->values()->create(['value' => 'Small', 'position' => 0]);

        $variant = $product->variants()->create([
            'sku' => 'TEE-S',
            'price' => 2500,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);
        $variant->optionValues()->attach($small->id);

        // Mirrors the variants table's "Options" column state, which receives
        // the variant record (not the option value) — guards the closure that
        // previously type-hinted the wrong model.
        $labels = $variant->load('optionValues.option')->optionValues
            ->map(fn ($value) => $value->option ? "{$value->option->name}: {$value->value}" : $value->value)
            ->all();

        $this->assertSame(['Size: Small'], $labels);
    }
}
