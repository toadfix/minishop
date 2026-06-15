<?php

namespace Minishop\Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Models\Product;
use Minishop\Models\ProductImage;
use Minishop\Models\Review;
use Minishop\Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_active_products(): void
    {
        Product::factory(3)->create();
        Product::factory()->inactive()->create();

        $response = $this->getJson('/api/v1/products');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_inactive_products_are_excluded_from_listing(): void
    {
        Product::factory(2)->create();
        Product::factory(2)->inactive()->create();

        $response = $this->getJson('/api/v1/products');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_product_listing_is_paginated(): void
    {
        Product::factory(25)->create();

        $response = $this->getJson('/api/v1/products');

        $response->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_can_show_a_single_active_product_by_slug(): void
    {
        $product = Product::factory()->create(['name' => 'My Widget']);

        $response = $this->getJson("/api/v1/products/{$product->slug}");

        $response->assertOk()
            ->assertJsonPath('data.slug', $product->slug)
            ->assertJsonPath('data.name', 'My Widget');
    }

    public function test_product_images_expose_a_resolved_url(): void
    {
        $product = Product::factory()->create();
        ProductImage::factory()->create([
            'product_id' => $product->id,
            'variant_id' => null,
            'path' => 'products/widget.jpg',
        ]);

        $this->getJson("/api/v1/products/{$product->slug}")
            ->assertOk()
            ->assertJsonPath('data.images.0.path', 'products/widget.jpg')
            ->assertJsonPath('data.images.0.url', fn ($url) => is_string($url) && str_contains($url, 'products/widget.jpg'));
    }

    public function test_product_exposes_approved_rating_average_and_count(): void
    {
        $product = Product::factory()->create();
        Review::factory()->approved()->create(['product_id' => $product->id, 'rating' => 5]);
        Review::factory()->approved()->create(['product_id' => $product->id, 'rating' => 4]);
        Review::factory()->create(['product_id' => $product->id, 'rating' => 1]); // pending, excluded

        $this->getJson("/api/v1/products/{$product->slug}")
            ->assertOk()
            ->assertJsonPath('data.reviews_count', 2)
            ->assertJsonPath('data.rating_average', 4.5);
    }

    public function test_inactive_product_returns_404(): void
    {
        $product = Product::factory()->inactive()->create();

        $this->getJson("/api/v1/products/{$product->slug}")
            ->assertNotFound();
    }

    public function test_missing_product_slug_returns_404(): void
    {
        $this->getJson('/api/v1/products/non-existent-slug')
            ->assertNotFound();
    }
}
