<?php

namespace Minishop\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Minishop\Enums\ShippingMethodType;
use Minishop\Models\ShippingMethod;

/**
 * @extends Factory<ShippingMethod>
 */
class ShippingMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Standard Delivery', 'Express Delivery', 'Economy Shipping']),
            'description' => fake()->optional()->sentence(),
            'price' => fake()->randomElement([20000, 30000, 50000]),
            'is_free' => false,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 10),
            'type' => ShippingMethodType::FlatRate->value,
            'carrier' => null,
            'service_code' => null,
        ];
    }

    public function free(): static
    {
        return $this->state(['name' => 'Free Shipping', 'price' => 0, 'is_free' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function calculated(): static
    {
        return $this->state([
            'type' => ShippingMethodType::Calculated->value,
            'carrier' => 'canada_post',
            'service_code' => 'DOM.EP',
            'price' => 0,
            'is_free' => false,
        ]);
    }
}
