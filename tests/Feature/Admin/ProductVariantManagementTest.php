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
}
