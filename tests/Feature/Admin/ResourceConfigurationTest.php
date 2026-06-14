<?php

namespace Minishop\Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Filament\Resources\ActivityLogResource;
use Minishop\Filament\Resources\CustomerResource;
use Minishop\Filament\Resources\OrderResource;
use Minishop\Filament\Resources\OrderReturnResource;
use Minishop\Filament\Resources\ProductResource;
use Minishop\Filament\Resources\ProductResource\RelationManagers\ImagesRelationManager;
use Minishop\Filament\Resources\ProductResource\RelationManagers\OptionsRelationManager;
use Minishop\Filament\Resources\ProductResource\RelationManagers\VariantsRelationManager;
use Minishop\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Static configuration coverage for the admin resources: eager-load scoping
 * (N+1 guards) and resource wiring. No Filament component is rendered.
 */
class ResourceConfigurationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{class-string, list<string>}>
     */
    public static function eagerLoadProvider(): array
    {
        return [
            'activity log loads user' => [ActivityLogResource::class, ['user']],
            'customer loads user' => [CustomerResource::class, ['user']],
            'order loads customer + shipping' => [OrderResource::class, ['customer.user', 'shippingMethod']],
            'return loads order' => [OrderReturnResource::class, ['order']],
        ];
    }

    /**
     * @param  class-string  $resource
     * @param  list<string>  $expected
     */
    #[DataProvider('eagerLoadProvider')]
    public function test_resource_eager_loads_relations_to_prevent_n_plus_one(string $resource, array $expected): void
    {
        $eagerLoaded = array_keys($resource::getEloquentQuery()->getEagerLoads());

        foreach ($expected as $relation) {
            $this->assertContains($relation, $eagerLoaded, "{$resource} should eager-load {$relation}");
        }
    }

    public function test_product_resource_registers_its_relation_managers(): void
    {
        $this->assertSame(
            [OptionsRelationManager::class, VariantsRelationManager::class, ImagesRelationManager::class],
            ProductResource::getRelations(),
        );
    }

    public function test_product_resource_exposes_index_create_and_edit_pages(): void
    {
        $this->assertSame(
            ['index', 'create', 'edit'],
            array_keys(ProductResource::getPages()),
        );
    }
}
