<?php

namespace Minishop\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Minishop\Enums\ProductType;
use Minishop\Models\Product;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'type' => ProductType::Simple,
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->optional()->paragraph(),
            'price' => fake()->numberBetween(100, 99999),
            'compare_price' => null,
            'sku' => fake()->boolean(50) ? fake()->unique()->regexify('[A-Z]{3}-[0-9]{4}') : null,
            'stock_quantity' => fake()->numberBetween(0, 200),
            'is_active' => true,
            'on_sale' => false,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => ['stock_quantity' => 0]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    public function simple(): static
    {
        return $this->state(fn (array $attributes) => ['type' => ProductType::Simple]);
    }

    public function variable(): static
    {
        return $this->state(fn (array $attributes) => ['type' => ProductType::Variable]);
    }

    public function bundled(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductType::Bundled,
            'stock_quantity' => 0,
        ]);
    }

    public function bundledEmpty(): static
    {
        return $this->bundled();
    }

    public function onSale(): static
    {
        return $this->state(fn (array $attributes) => ['on_sale' => true]);
    }
}
