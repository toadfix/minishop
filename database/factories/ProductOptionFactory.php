<?php

namespace Minishop\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Minishop\Models\Product;
use Minishop\Models\ProductOption;

/**
 * @extends Factory<ProductOption>
 */
class ProductOptionFactory extends Factory
{
    protected $model = ProductOption::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => $this->faker->randomElement(['Size', 'Color', 'Material']),
            'position' => 0,
        ];
    }
}
