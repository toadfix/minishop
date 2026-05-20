<?php

namespace Minishop\Database\Seeders;

use Illuminate\Database\Seeder;
use Minishop\Models\Category;
use Minishop\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::query()->whereNull('parent_id')->pluck('id')->toArray();

        Product::factory(20)->create()->each(function (Product $product) use ($categories) {
            $randomCategories = array_rand(array_flip($categories), min(2, count($categories)));
            $product->categories()->sync((array) $randomCategories);
        });
    }
}
