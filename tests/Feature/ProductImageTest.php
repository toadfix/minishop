<?php

namespace Minishop\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Minishop\Models\Product;
use Minishop\Models\ProductImage;
use Minishop\Tests\TestCase;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_url_accessor_resolves_against_the_configured_image_disk(): void
    {
        // A disk distinct from the default 'public', with its own URL root, so a
        // hardcoded default would produce a different URL than the assertion.
        config([
            'filesystems.disks.images_test' => [
                'driver' => 'local',
                'root' => storage_path('app/images_test'),
                'url' => 'https://cdn.example.com/img',
                'visibility' => 'public',
            ],
            'minishop.image_disk' => 'images_test',
        ]);

        $image = ProductImage::factory()->make(['path' => 'products/photo.jpg']);

        $this->assertSame('https://cdn.example.com/img/products/photo.jpg', $image->url);
    }

    public function test_deleting_a_product_removes_its_images_from_the_configured_disk(): void
    {
        Storage::fake('images_test');
        config(['minishop.image_disk' => 'images_test']);

        Storage::disk('images_test')->put('products/photo.jpg', 'binary');

        $product = Product::factory()->create();
        ProductImage::factory()->create([
            'product_id' => $product->id,
            'path' => 'products/photo.jpg',
        ]);

        $product->delete();

        Storage::disk('images_test')->assertMissing('products/photo.jpg');
    }
}
