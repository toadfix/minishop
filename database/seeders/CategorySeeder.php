<?php

namespace Minishop\Database\Seeders;

use Illuminate\Database\Seeder;
use Minishop\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $rootCategories = [
            ['name' => 'Clothing', 'slug' => 'clothing', 'description' => 'Apparel and fashion items'],
            ['name' => 'Electronics', 'slug' => 'electronics', 'description' => 'Gadgets and electronic devices'],
            ['name' => 'Home & Living', 'slug' => 'home-living', 'description' => 'Furniture and home decor'],
            ['name' => 'Sports', 'slug' => 'sports', 'description' => 'Sporting goods and outdoor equipment'],
            ['name' => 'Books', 'slug' => 'books', 'description' => 'Books, e-books, and publications'],
        ];

        foreach ($rootCategories as $data) {
            $parent = Category::query()->create(array_merge($data, ['is_active' => true]));

            Category::factory(2)->create(['parent_id' => $parent->id]);
        }
    }
}
