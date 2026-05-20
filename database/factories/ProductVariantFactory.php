<?php

namespace Minishop\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Minishop\Models\Product;
use Minishop\Models\ProductVariant;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => $this->faker->boolean(50) ? strtoupper($this->faker->unique()->bothify('VAR-###??')) : null,
            'price' => $this->faker->boolean(50) ? $this->faker->numberBetween(500, 9999) : null,
            'stock_quantity' => $this->faker->numberBetween(0, 100),
            'low_stock_threshold' => null,
            'low_stock_notified' => false,
            'is_active' => true,
        ];
    }

    public function belowThreshold(int $threshold = 5): static
    {
        return $this->state([
            'stock_quantity' => $threshold - 1,
            'low_stock_threshold' => $threshold,
            'low_stock_notified' => false,
        ]);
    }

    public function atThreshold(int $threshold = 5): static
    {
        return $this->state([
            'stock_quantity' => $threshold,
            'low_stock_threshold' => $threshold,
            'low_stock_notified' => false,
        ]);
    }

    public function aboveThreshold(int $threshold = 5): static
    {
        return $this->state([
            'stock_quantity' => $threshold + 10,
            'low_stock_threshold' => $threshold,
            'low_stock_notified' => false,
        ]);
    }

    public function alreadyNotified(int $threshold = 5): static
    {
        return $this->state([
            'stock_quantity' => $threshold - 1,
            'low_stock_threshold' => $threshold,
            'low_stock_notified' => true,
        ]);
    }
}
