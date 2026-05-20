<?php

namespace Minishop\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Minishop\Models\ProductOption;
use Minishop\Models\ProductOptionValue;

/**
 * @extends Factory<ProductOptionValue>
 */
class ProductOptionValueFactory extends Factory
{
    protected $model = ProductOptionValue::class;

    public function definition(): array
    {
        return [
            'product_option_id' => ProductOption::factory(),
            'value' => $this->faker->word(),
            'position' => 0,
        ];
    }
}
